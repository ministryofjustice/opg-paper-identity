SHELL = '/bin/bash'

help:
	@grep --no-filename -E '^[0-9a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: scripts/localstack/init/private_key.pem ## Build containers
	docker compose build --parallel api front api-test yoti-mock

up: ## Start application
	docker compose up -d front-web

down: ## Stop application
	docker compose down

api-psalm: ## Run Psalm checks against API code
	docker compose -p api-psalm run --rm api-test vendor/bin/psalm -c ./psalm.xml --report=build/psalm-junit.xml

api-phpcs: ## Run PHPCS checks against API code
	docker compose -p api-phpcs run --rm api-test vendor/bin/phpcs --report=junit --report-file=build/phpcs-junit.xml

api-unit-test: scripts/localstack/init/private_key.pem ## Run API unit tests
	docker compose -p api-unit-test run --rm api-test vendor/bin/phpunit --log-junit=build/phpunit-junit.xml

front-psalm: ## Run Psalm checks against front end code
	docker compose -p front-psalm run --rm front-test vendor/bin/psalm -c ./psalm.xml --report=build/psalm-junit.xml

front-phpcs: ## Run PHPCS checks against front end code
	docker compose -p front-phpcs run --rm front-test vendor/bin/phpcs --report=junit --report-file=build/phpcs-junit.xml

front-unit-test: ## Run front end unit tests
	docker compose -p front-unit-test run --rm front-test vendor/bin/phpunit --log-junit=build/phpunit-junit.xml

api-test:
	@${MAKE} api-psalm api-phpcs api-unit-test -j 3

front-test:
	@${MAKE} front-psalm front-phpcs front-unit-test -j 3

clean-junit-output:
	sed -i 's/file="\/var\/www\//file="/g' ./service-api/build/phpunit-junit.xml
	sed -i 's/file="\/var\/www\//file="/g' ./service-front/build/phpunit-junit.xml
	sed -i -E 's/testcase name="(.*?)\/var\/www\/([^ ]+?)( \(([0-9]+):[0-9]+\))?"/& file="\2" line="\4"/g' ./service-api/build/phpcs-junit.xml
	sed -i -E 's/testcase name="(.*?)\/var\/www\/([^ ]+?)( \(([0-9]+):[0-9]+\))?"/& file="\2" line="\4"/g' ./service-front/build/phpcs-junit.xml
	sed -i -E 's/testcase name="(.*?):([0-9]+)"/& file="\1" line="\2"/g' ./service-api/build/psalm-junit.xml
	sed -i -E 's/testcase name="(.*?):([0-9]+)"/& file="\1" line="\2"/g' ./service-front/build/psalm-junit.xml

scripts/localstack/init/private_key.pem:
	openssl genpkey -algorithm RSA -out scripts/localstack/init/private_key.pem -pkeyopt rsa_keygen_bits:2048
	openssl rsa -pubout -in scripts/localstack/init/private_key.pem -out scripts/localstack/init/public_key.pem
