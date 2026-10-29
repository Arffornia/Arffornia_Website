# Thin wrappers over the native tools. `make check` is what CI runs.
.PHONY: check lint test build fmt pint-test phpstan install

install:
	composer install
	npm ci

# build runs before test: feature tests render Blade views that need public/build/manifest.json.
check: lint build test

lint:
	composer validate --no-check-publish

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
