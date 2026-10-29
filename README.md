# Arffornia Website

Public website and central API of the Arffornia modded Minecraft server. It is a Laravel 12 monolith:
the public site (home, news, rules, profiles, shop, vote, progression graph), the admin panel, and the
REST API used by the NeoForge mod, the Velocity proxy and the launcher. It is the source of truth for
players, progression, teams, shop and votes. Players sign in with Microsoft / Xbox Live; API callers use
Laravel Sanctum bearer tokens. Production runs on Kubernetes (Helm chart in `charts/arffornia-website`).

Architecture docs (coming soon): https://arffornia.github.io/docs/

## Requirements

- PHP 8.3 with `pdo_pgsql`, `pdo_sqlite`, `zip`, `mbstring`, `intl`
- Composer 2
- Node.js 22 LTS and npm (Vite 8 needs Node >= 20.19)
- PostgreSQL in production; SQLite is enough for local work and tests
- Docker (optional, for the compose setups and image builds)

## Quickstart

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite       # .env.example uses DB_CONNECTION=sqlite
php artisan migrate --seed           # demo data (needs dev dependencies)
npm run build                        # or `npm run dev` for Vite hot reload
php artisan serve
```

Docker alternative (php-fpm + nginx + Vite):

```bash
docker compose -f docker-compose-dev.yaml up --build -d
```

## Commands

| Command | What it does |
|---|---|
| `make check` | What CI runs: `composer validate`, frontend build, PHPUnit |
| `make test` | PHPUnit |
| `make build` | Frontend build (`npm run build`) |
| `make lint` | `composer validate` + i18n lint (hardcoded text in Blade) |
| `make fmt` / `make pint-test` | Pint fix / check (not gating yet) |
| `make phpstan` | Larastan level 5 (not part of `check` yet: existing errors) |

## API

All routes are prefixed with `/api`. The source of truth is `routes/api.php` (`php artisan route:list --path=api`).

| Method | Path | Auth | Used by |
|---|---|---|---|
| GET | `best_player_point/{size}`, `best_player_vote/{size}`, `best_player_vote/all-time/{size}` | public | launcher |
| GET | `profile/{playerName}`, `profile/uuid/{playerUuid}` | public | launcher, mod |
| GET | `stages`, `stages/player/get/{uuid}`, `milestone/get/{id}`, `progression/config`, `progression/{id}`, `recipes`, `recipes/type/{type}` | public | mod, site JS |
| GET | `launcherImages` | public | launcher |
| GET | `shop/bestSallers/{size}`, `shop/newest/{size}`, `shop/sales/{size}`, `shop/item/{item}` | public | launcher, site JS |
| POST | `auth/token/svc` | `client_id` + `client_secret` | mod, proxy |
| POST | `auth/token/session` | web session | site JS |
| POST | `auth/token/ms` | Microsoft access token | none known |
| POST | `vote/nuvotifier` | role `nuvotifier_service` | proxy |
| POST | `player/ensure-exists` | role `admin` or `user_editor` | mod |
| POST | `teams/player/join`, `teams/player/leave`, `teams/disband` | role `admin` or `team_editor` | mod |
| GET/POST | `progression/*`, `milestones/{id}/add-unlock-from-game`, `milestones/{id}/set-requirements`, `milestone-items/move`, `migration/*` | role `admin` or `progression_editor` | mod |
| POST/PUT/DELETE | `stages*`, `graphs*`, `milestones*`, `milestone-closures`, `unlocks*`, `requirements*`, `launcherImages/{id}/toggle-prod` | role `admin` | admin panel, stages editor |

Get a service token:

```sh
curl -X POST http://localhost:8000/api/auth/token/svc \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"client_id": "svc_example", "client_secret": "<secret>"}'
```

The mod also reads and updates the `pending_rewards` table directly over JDBC, so that table's schema is
a contract with `Arffornia_Mods`.

## Configuration

Configuration is environment-driven (see `.env.example`). Main variables (no values here):

| Variable | Purpose |
|---|---|
| `APP_KEY`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `LOG_LEVEL` | Laravel basics (`APP_DEBUG=false` in production) |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Database |
| `AZURE_OAUTH_CLIENT_ID`, `AZURE_OAUTH_CLIENT_SECRET`, `AZURE_OAUTH_REDIRECT_URI` | Microsoft login |
| `SVC_ACCOUNT_<KEY>_CLIENT_ID`, `_CLIENT_SECRET`, `_NAME`, `_ROLES` | Service accounts, synced by `php artisan app:sync-service-accounts` |
| `VOTE_SMC_SERVER_ID` | serveurs-minecraft.org vote verification |
| `SANCTUM_STATEFUL_DOMAINS`, `SESSION_*` | Session and Sanctum SPA auth |
| `APP_WEB_DEFAULT_LOCALE` | Website language when the visitor has no cookie or matching `Accept-Language` (`fr` by default; API stays `en`) |

In Kubernetes, non-secret values come from a ConfigMap and secrets from Vault (see the `infra` repository).
The Helm chart values are documented in `charts/arffornia-website/values.yaml`.

## Release process

- Pushing to `main` builds and pushes `ghcr.io/arffornia/arffornia_website:latest` and
  `ghcr.io/arffornia/nginx_arffornia_website:latest` (signed with cosign).
- Tags `vX.Y.Z` (optionally prefixed `laravel-` or `nginx-`) publish versioned images.
- Tags `chart-X.Y.Z` publish the Helm chart to GHCR.
- Deployment is done by ArgoCD from the `infra` repository.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and [AGENTS.md](AGENTS.md). Security reports: [SECURITY.md](SECURITY.md).

## License

No license file yet (tracked in #78). `composer.json` still carries the Laravel skeleton's MIT metadata.
