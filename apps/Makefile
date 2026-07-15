# Common tasks for apps under this monorepo.
# Run from apps/: `make up`, `make test`, etc.
# Override the app with: `make up APP=platform`

APP ?= platform
APP_DIR := $(APP)

# Prefer Sail when the binary exists; fall back to host tooling.
SAIL := ./vendor/bin/sail
HAS_SAIL := $(wildcard $(APP_DIR)/vendor/bin/sail)

.PHONY: help up down build rebuild shell artisan test lint pint types security \
	composer-install npm-install setup migrate logs open

help: ## Show available targets
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  %-18s %s\n", $$1, $$2}'

# --- Sail / Docker -----------------------------------------------------------

up: ## Start Sail containers in the background
	cd $(APP_DIR) && $(SAIL) up -d

down: ## Stop Sail containers
	cd $(APP_DIR) && $(SAIL) down

build: ## Build the Sail app image (needed once before first up)
	cd $(APP_DIR) && $(SAIL) build

rebuild: ## Rebuild Sail image without cache
	cd $(APP_DIR) && $(SAIL) build --no-cache

logs: ## Follow Sail container logs
	cd $(APP_DIR) && $(SAIL) logs -f

open: ## Open the app in the browser
	cd $(APP_DIR) && $(SAIL) open

shell: ## Open a shell inside the Sail app container
	cd $(APP_DIR) && $(SAIL) shell

# --- App commands (via Sail when available) ----------------------------------

artisan: ## Run an artisan command: make artisan ARGS="migrate"
	cd $(APP_DIR) && $(SAIL) artisan $(ARGS)

migrate: ## Run database migrations
	cd $(APP_DIR) && $(SAIL) artisan migrate

test: ## Run the test suite
	cd $(APP_DIR) && $(SAIL) artisan test --compact $(ARGS)

lint: ## Format PHP (Pint) and check frontend lint
	cd $(APP_DIR) && $(SAIL) bin pint --dirty
	cd $(APP_DIR) && $(SAIL) npm run lint:check

pint: ## Run Laravel Pint only
	cd $(APP_DIR) && $(SAIL) bin pint --dirty

types: ## Run PHPStan / Larastan
	cd $(APP_DIR) && $(SAIL) composer types:check

security: ## Audit composer and npm dependencies
	cd $(APP_DIR) && $(SAIL) composer security:check

# --- Install / setup ---------------------------------------------------------

composer-install: ## Install PHP dependencies
	cd $(APP_DIR) && composer install

npm-install: ## Install Node dependencies
	cd $(APP_DIR) && npm install

setup: ## First-time setup: composer, .env, key, migrate, npm, build
	cd $(APP_DIR) && composer run setup
