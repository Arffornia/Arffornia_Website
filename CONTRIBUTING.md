# Contributing

Thanks for helping with the Arffornia website. Agents and newcomers: read [AGENTS.md](AGENTS.md) first.

## Setup

See the Quickstart in [README.md](README.md): `composer install`, `npm ci`, `.env`, `php artisan migrate --seed`.

## Workflow

1. Pick or open an issue. Behaviour changes need an issue; design questions get the `needs-decision` label.
2. Branch from `main`: `fix/<slug>`, `feat/<slug>`, `security/<slug>`, `test/<slug>`, `ci/<slug>`, `docs/<slug>`.
3. Commit with [Conventional Commits](https://www.conventionalcommits.org/): `fix(shop): ...`, `test(api): ...`.
4. Run `make check` (composer validate, PHPUnit, frontend build). It is what CI runs.
5. Open a PR using the template (What / Why / How it was tested / Risk & revert) with `Closes #n`.

## Rules

- English for code, comments, docs, commits and PRs. Player-facing text goes through i18n (en + fr).
- No secrets in the repo. Configuration comes from environment variables.
- Do not change API response shapes used by the mod, proxy or launcher without an issue.
- Security problems: see [SECURITY.md](SECURITY.md), never a public issue.
