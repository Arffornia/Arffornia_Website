# Thin wrappers over the native tools. `make check` is what CI runs.
.PHONY: check lint lint-i18n test test-js build fmt pint-test phpstan install tokens tokens-check icons-export icons-publish

install:
	composer install
	npm ci

# build runs before test: feature tests render Blade views that need public/build/manifest.json.
check: lint build test-js test

lint: lint-i18n
	composer validate --no-check-publish

# Flags user-visible text hardcoded in Blade views (allow-list: .i18n-lint-ignore)
lint-i18n:
	php scripts/lint-i18n.php

test:
	vendor/bin/phpunit

# Progression graph component (packages/progression-graph): types and unit tests
test-js:
	npm run typecheck
	npm test

build:
	npm run build

fmt:
	vendor/bin/pint

# Not part of `check` yet: the code base has never been formatted with Pint (see the CI quality gate issue).
pint-test:
	vendor/bin/pint --test

# Not part of `check` yet: existing errors need a baseline first.
phpstan:
	vendor/bin/phpstan analyse --memory-limit=1G

TOKENS_URL := https://raw.githubusercontent.com/Arffornia/Arffornia_Assets/main/ui/tokens/dist/tokens.css

tokens:
	curl -fsSL $(TOKENS_URL) -o public/css/tokens.css

tokens-check:
	curl -fsSL $(TOKENS_URL) | diff -u public/css/tokens.css -

# Item icons (scripts/icons/README.md): PACK is a launcher install of the pack, ICONS a work folder.
ICONS ?= $(HOME)/.cache/arffornia/icons

icons-export:
	scripts/icons/export.sh "$(PACK)" "$(ICONS)"

icons-publish:
	scripts/icons/publish.sh "$(ICONS)"
