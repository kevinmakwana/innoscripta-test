#!/usr/bin/env make

.PHONY: enable-git-hooks

enable-git-hooks:
	@echo "Enabling git hooks from .githooks directory..."
	@chmod +x .githooks/pre-commit || true
	@git config core.hooksPath .githooks || echo "Run 'git config core.hooksPath .githooks' in your repo if this fails"
	@echo "Git hooks enabled (if run inside a git repo)."
.PHONY: build test shell composer

build:
	docker compose build --no-cache

shell:
	docker compose run --rm app sh

composer:
	docker compose run --rm app composer $(filter-out $@,$(MAKECMDGOALS))

test:
	docker compose run --rm app bash -lc "composer install --no-interaction --prefer-dist && ./vendor/bin/phpunit --testdox"

.PHONY: up install migrate seed logs build-prod composer-install-in-container

up:
	docker compose up -d --build

install: composer-install-in-container

composer-install-in-container:
	docker compose run --rm app bash -lc "composer install --no-interaction --prefer-dist"

migrate:
	docker compose exec -T app bash -lc "php artisan migrate --no-interaction"

seed:
	docker compose exec -T app bash -lc "php artisan db:seed --no-interaction"

logs:
	docker compose logs -f app

build-prod:
	docker build --target prod -t innoscripta-test:prod .

.PHONY: smoke
smoke:
	@echo "Running smoke run script..."
	chmod +x scripts/smoke_run.sh || true
	./scripts/smoke_run.sh
