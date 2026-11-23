# AGENTS.md

Guide for AI agents and newcomers working on the Arffornia website.

## Purpose

Laravel 13 monolith (PHP 8.5): public site, admin panel and the central REST API of Arffornia (players,
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
| `packages/progression-graph` | Progression graph viewer in TypeScript, shared with the game and the launcher (spec 018); see its README |
| `config/` | Laravel config (`app.php` has Azure OAuth, `voting.php` vote sites) |
| `tests/Feature`, `tests/Unit` | PHPUnit 13 |
| `prod.Dockerfile`, `nginx.Dockerfile`, `.docker/` | php-fpm and nginx images |
| `charts/arffornia-website` | Helm chart (php-fpm + nginx in one pod) |
| `.github/workflows` | CI, image build (`latest` for prod), preview images (`main`, `pr-<n>-<sha7>` for staging and playtest slots), Helm publish, Trivy |

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
  API routes use `App\Http\Middleware\SetApiLocale`: `?locale=`, `locale` cookie, `Accept-Language`, then
  `APP_API_DEFAULT_LOCALE` (`en`); the chosen locale is sent back in `Content-Language`. API JSON messages
  live in `lang/{en,fr}/api.php`; keep the English lines stable (the mod and proxy send no locale).
- JS: Blade renders the strings as JSON (`window.AppData.i18n = @json(__('shop.js'))`,
  `window.ArfforniaI18n.widgets` in the layout); `resources/js/i18n.js` has a `t()` helper.
- Database content (news, shop items, milestones) is not translated yet (see #100).
- `make lint` runs `scripts/lint-i18n.php`, which flags bare text in Blade and literal `'message'`/`'error'`
  values or `abort()` texts in controllers, middleware and services. Justified exceptions go in
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

- The lock is resolved for PHP 8.5 (`config.platform.php`). Bump it together with the runtime image
  (`prod.Dockerfile`) and the CI `php-version` (`ci.yml`, `e2e.yml`).
- Cache and session cookie prefixes keep the Laravel 12 formula in `config/` on purpose: the Laravel 13
  skeleton default would log everyone out and orphan cache keys. Do not "sync" them with the skeleton.
- OPcache is built into PHP 8.5; do not add it to `docker-php-ext-install`.
- Production images are built with `composer install --no-dev`: faker, ignition, phpunit are absent, so
  seeders that use factories do not run in prod.
- Rewards reach the mod through `/api/rewards/*` as typed `contents` (`App\Rewards\RewardCatalog`); never store or run console commands. Mod versions before the rewards API still read `pending_rewards.commands` over JDBC.
- Progression (spec 017, `app/Progression`): the editable tables (`stages`, `progression_graphs`, `milestones`...) are the working draft; `POST /api/progression/drafts/current/publish` freezes it as a numbered snapshot (`model_snapshots`). Player state lives in `progression_milestones`/`progression_deliveries`/`progression_events`, by milestone **key**, never row id. `completed_milestones` and `max_stage_id` are still written for clients on the old routes. Until the mod and launcher leave the old routes, the draft tables are what they read: a draft edit is live for them before any publish. A frozen document keeps the `status` it was published with; the row's status (header `X-Model-Status`) says whether it is still current. Every route that serves a progression checks `ProgressionVisibility`. `resources/schemas/progression-model.schema.json` is a copy of the one in Arffornia/docs: refresh it, never edit it here.
- The in-game viewer (spec 018 D2 a) is `/embed/progression`: the same component without site chrome, off with the viewer flag. The game server mints a read-only player token (`POST /api/progression/embed-tokens`, `App\Progression\EmbedTokens`, HMAC, 15 min); the mod hands it to the page through its CEF bridge, never in a URL, and the page reads `GET /api/embed/progression/state` with it. Targeting goes through the game server (`set-target` with `milestone_key`, rule 3 checked on both sides).
- The admin editor (`/admin/progression`, spec 019, `app/Progression/Editor`) edits its own draft document (`progression_drafts`), not the tables: nothing it does is live before a publish. Every action carries the draft `revision`; a stale one gets 409. Undo and redo replay the entity changes stored per action (`progression_draft_entries`, also the audit log). Publishing an editor draft writes it into the tables and freezes the snapshot in one transaction; errors refuse it, warnings need `confirm_warnings`.
- Playwright logs in through `/_e2e/login/{name}`, registered only when `APP_ENV=testing` and `E2E_LOGIN=true` (`e2e/serve.sh`). The seeded admin is `E2E_Admin`.
- `arffornia/minecraft-oauth` is required as `dev-main` (fork of an abandoned package).
- Mojang's `/user/profiles/{uuid}/names` endpoint no longer exists; code using it is dead.
- `public/css/tokens.css` is a copy of `Arffornia_Assets/ui/tokens/dist/tokens.css` (spec 018): never edit it, refresh it with `make tokens`; CI (`make tokens-check`) fails when it drifts from Assets main. Use `var(--arf-*)` for brand colours and fonts.
- Item icons by registry id are not in this repo: `/icons/{size}/{ns}/{path}.png` and `/icons/manifest.json` come from
  the `item-icons` Garage bucket (contract and refresh in `scripts/icons/README.md`). `public/images/item_textures/`
  (display-name files, `sync_textures.sh`) is the old export still read through milestone `image_path`; drop it once
  nothing reads `image_path`.
- Launcher images are stored on the php container's local disk (not shared with nginx, not persistent).
- phpstan currently reports many errors; do not add new ones.

## Related repos

`Arffornia_Mods` (NeoForge mod), `Arffornia_Proxy` (Velocity), `Arffornia_Launcher_V.5.2` (Electron),
`minecraft-oauth` (Microsoft login library), `keycloak-theme-arffornia` (infra Keycloak login theme, not
player login), `infra` (Kubernetes/GitOps deployment of this site).
