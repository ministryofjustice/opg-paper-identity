SHELL = '/bin/bash'

build: ## Build containers
	docker compose build --parallel api front api-test

up: ## Start application
	docker compose up -d front

down: ## Stop application
	docker compose down

api-unit-test:
	docker compose run --rm api-test vendor/bin/phpunit

front-unit-test:
	docker compose run --rm front-test vendor/bin/phpunit
