SHELL = '/bin/bash'

build: ## Build containers
	docker compose build --parallel api front api-test

up: ## Start application
	docker compose up -d front

down: ## Stop application
	docker compose down

api-psalm:
	docker compose run --rm api-test vendor/bin/psalm -c ./psalm.xml --report=build/psalm-junit.xml

api-phpcs:
	docker compose run --rm api-test vendor/bin/phpcs --report=junit --report-file=build/phpcs-junit.xml

api-unit-test:
	docker compose run --rm api-test vendor/bin/phpunit --log-junit=build/phpunit-junit.xml

front-psalm:
	docker compose run --rm front-test vendor/bin/psalm -c ./psalm.xml --report=build/psalm-junit.xml

front-phpcs:
	docker compose run --rm front-test vendor/bin/phpcs --report=junit --report-file=build/phpcs-junit.xml

front-unit-test:
	docker compose run --rm front-test vendor/bin/phpunit --log-junit=build/phpunit-junit.xml

api-test:
	@${MAKE} api-psalm api-phpcs api-unit-test -j 3

front-test:
	@${MAKE} front-psalm front-phpcs front-unit-test -j 3
