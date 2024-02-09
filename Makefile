SHELL = '/bin/bash'

build: ## Build containers
	docker compose build --parallel api front

up: ## Start application
	docker compose up -d front

down: ## Stop application
	docker compose down
