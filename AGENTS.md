# AGENTS.md

Guide for AI agents and newcomers working on the Arffornia website.

## Purpose

Laravel 12 monolith: public site, admin panel and the central REST API of Arffornia (players,
progression, teams, shop, votes). Consumers: the NeoForge mod (`Arffornia_Mods`), the Velocity proxy
(`Arffornia_Proxy`) and the launcher (`Arffornia_Launcher_V.5.2`). Changing an API response shape or the
`pending_rewards` table breaks them.

## Repo map

| Path | What |
|---|---|
| `routes/web.php`, `routes/api.php` | Web pages and `/api` routes (role middleware `anyRole`/`allRole`) |
| `app/Http/Controllers/` | Controllers (`StagesController` is the large progression/graph one) |
| `app/Services/`, `app/Repositories/` | Business logic and Eloquent queries (Controller -> Service -> Repository) |
| `app/Models/` | Eloquent models; roles are a comma-separated `role` string on `User`/`ServiceAccount` |
| `app/Http/Middleware/CheckAnyRole.php` | Role checks |
| `app/Console/Commands/SyncServiceAccounts.php` | Syncs `SVC_ACCOUNT_*` env vars into `service_accounts` |
| `database/migrations`, `database/factories`, `database/seeders` | Schema, factories, demo seed |
| `resources/views`, `resources/js`, `resources/css`, `public/js` | Blade views and frontend (Vite 8) |
| `config/` | Laravel config (`app.php` has Azure OAuth, `voting.php` vote sites) |
| `tests/Feature`, `tests/Unit` | PHPUnit 12 |
| `prod.Dockerfile`, `nginx.Dockerfile`, `.docker/` | php-fpm and nginx images |
| `charts/arffornia-website` | Helm chart (php-fpm + nginx in one pod) |
| `.github/workflows` | CI, image build, Helm publish, Trivy |

## Commands

```bash
make check     # composer validate + npm run build + phpunit (what CI runs)
make test      # vendor/bin/phpunit (sqlite :memory:)
make build     # npm run build
make lint      # composer validate + i18n lint (scripts/lint-i18n.php)
make fmt       # pint (pint-test: check only, not gating yet)
make phpstan   # larastan level 5 (existing errors, not gating yet)
php artisan serve
php artisan route:list --path=api
```

## Conventions

- English for code, comments, docs, commits, issues and PRs.
- Player-facing text goes through Laravel i18n; never hardcode it (see "i18n" below).
- Conventional Commits (`fix(shop): ...`). Branches `fix/<slug>`, `security/<slug>`, `test/<slug>`, `ci/<slug>`, `docs/<slug>`.
- One PR per issue, `Closes #n`. PR body: What / Why / How it was tested / Risk & revert.
- Keep the layering: controllers stay thin, logic in services, queries in repositories.
- Behaviour changes need an issue. Design choices are for the maintainer (label `needs-decision`).

## i18n (English and French)

- Convention: **PHP array files** `lang/{en,fr}/<group>.php`, one group per page or area (`nav`, `home`,
  `rules`, `shop`, `vote`, `stages`, `messages`...), used as `__('group.key')`. No JSON lang files.
  Framework files (`validation`, `auth`, `pagination`, `passwords`) are published from Laravel; the
  French ones are adapted from Laravel-Lang (MIT).
- Every key exists in both locales with the same `:placeholders` (`tests/Unit/TranslationParityTest.php`).
- Locale: `App\Http\Middleware\SetLocale` on the `web` group: `locale` cookie (set by the EN/FR
  switcher, `POST /locale`), then `Accept-Language`, then `APP_WEB_DEFAULT_LOCALE` (`fr`). No URL prefix.
  API routes stay in `config('app.locale')` = `en`; do not change English strings that the mod, proxy or
  launcher read.
- JS: Blade renders the strings as JSON (`window.AppData.i18n = @json(__('shop.js'))`,
  `window.ArfforniaI18n.widgets` in the layout); `resources/js/i18n.js` has a `t()` helper.
- Database content (news, shop items, milestones) is not translated yet (see #100).
- `make lint` runs `scripts/lint-i18n.php`, which flags bare text in Blade. Justified exceptions go in
  `.i18n-lint-ignore`.

## Hard rules

- Never commit secrets or `.env` files. Config values come from env vars (ConfigMap + Vault in prod).
- Never replace the owner's libraries (Nexus_*, launcher modules) with third-party ones.
- Never merge, tag, release or push to `main`. Open PRs.
- Never put exploit details for unfixed weaknesses in public issues or PRs.
- Do not change the JSON shape of API responses used by the mod, proxy or launcher without a ticket.

## Testing strategy

- Feature tests with `RefreshDatabase` on sqlite `:memory:` (configured in `phpunit.xml`).
- API contract tests: status code, JSON shape and role matrix for each route the mod, proxy and launcher use.
- Use factories (`database/factories`); add missing ones rather than inserting rows by hand.
- Mock external HTTP with `Http::fake()` and bind fakes for `MinecraftOauth`.
- Postgres-only behaviour (row locks, `setval`) should also run against Postgres in CI (planned).
- E2E (Playwright) is planned; see the issue tracker.

## Gotchas

- The lock is pinned to PHP 8.3 (`config.platform.php`). Bump it together with the runtime image.
- Production images are built with `composer install --no-dev`: faker, ignition, phpunit are absent, so
  seeders that use factories do not run in prod.
- The mod reads `pending_rewards` over JDBC: schema changes there are cross-repo changes.
- `arffornia/minecraft-oauth` is required as `dev-main` (fork of an abandoned package).
- Mojang's `/user/profiles/{uuid}/names` endpoint no longer exists; code using it is dead.
- Launcher images are stored on the php container's local disk (not shared with nginx, not persistent).
- phpstan currently reports many errors; do not add new ones.

## Related repos

`Arffornia_Mods` (NeoForge mod), `Arffornia_Proxy` (Velocity), `Arffornia_Launcher_V.5.2` (Electron),
`minecraft-oauth` (Microsoft login library), `keycloak-theme-arffornia` (infra Keycloak login theme, not
player login), `infra` (Kubernetes/GitOps deployment of this site).
