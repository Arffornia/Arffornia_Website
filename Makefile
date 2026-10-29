# Thin wrappers over the native tools. `make check` is what CI runs.
.PHONY: check lint lint-i18n test build fmt pint-test phpstan install

install:
	composer install
	npm ci

check: lint test build

lint: lint-i18n
	composer validate --no-check-publish

# Flags user-visible text hardcoded in Blade views (allow-list: .i18n-lint-ignore)
lint-i18n:
	php scripts/lint-i18n.php

test:
	vendor/bin/phpunit

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
