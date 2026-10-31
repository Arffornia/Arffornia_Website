# End-to-end tests (Playwright)

Browser tests for the main website journeys. `playwright.config.ts` starts the app with `serve.sh`:
a fresh SQLite database (`database/e2e.sqlite`), the deterministic `Database\Seeders\E2ESeeder`, and
`php artisan serve` on port 8001.

```bash
npm run build                                   # frontend assets (from the repo root)
npm ci --prefix e2e
npx --prefix e2e playwright install chromium    # once
cd e2e && npx playwright test
```

Set `E2E_BASE_URL` to run against an already running instance (for example docker compose).

Covered: anonymous browsing (home podium, profile, news, rules, shop, stages, vote), the login redirect,
admin access denied. Next (issue #67): a testing-only login route, shop purchase, vote, stages editor and
admin journeys, and an axe accessibility check.
