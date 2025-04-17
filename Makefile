SHELL = '/bin/bash'
.PHONY: build

all: front-psalm api-psalm front-phpcs api-phpcs api-unit-test front-unit-test build scan cypress down

help:
	@grep --no-filename -E '^[0-9a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: ## Build containers
	docker compose build --parallel

up: ## Start application
	docker compose up -d front-web

down: ## Stop application
	docker compose down

download-reference-data:
	curl https://api.yoti.com/idverify/v1/supported-documents > ./service-front/module/Application/config/yoti-supported-documents.json

api-psalm: ## Run Psalm checks against API code
	docker compose -p api-psalm run --rm --no-deps api-test vendor/bin/psalm -c ./psalm.xml --report=build/psalm-junit.xml

api-phpcs: ## Run PHPCS checks against API code
	docker compose -p api-phpcs run --rm --no-deps api-test vendor/bin/phpcs --report=junit --report-file=build/phpcs-junit.xml

api-unit-test: ## Run API unit tests
	docker compose -p api-unit-test run --rm --no-deps api-test vendor/bin/phpunit --log-junit=build/phpunit-junit.xml

front-psalm: ## Run Psalm checks against front end code
	docker compose -p front-psalm run --rm --no-deps front-test vendor/bin/psalm -c ./psalm.xml --report=build/psalm-junit.xml

front-phpcs: ## Run PHPCS checks against front end code
	docker compose -p front-phpcs run --rm --no-deps front-test vendor/bin/phpcs --report=junit --report-file=build/phpcs-junit.xml

front-unit-test: ## Run front end unit tests
	mkdir -m 0777 -p ${PWD}/build/output/pacts
	docker compose -p front-unit-test run --rm --no-deps --volume ${PWD}/build/output/pacts:/output front-test vendor/bin/phpunit --log-junit=build/phpunit-junit.xml

	@if [ ! "$(PACT_BROKER_PASSWORD)" = "" ]; then make publish-pacts; fi

publish-pacts:
	docker run --rm \
		-v ${PWD}/build/output/pacts:/tmp/output \
		-e PACT_BROKER_PASSWORD \
		pactfoundation/pact-cli:latest \
		publish \
		/tmp/output \
		--broker-base-url https://pact-broker.api.opg.service.justice.gov.uk \
		--broker-username admin \
		--consumer-app-version $(PACT_CONSUMER_VERSION) \
		--branch $(PACT_CONSUMER_BRANCH) \
		--tag $(PACT_CONSUMER_TAG)

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

cypress:
	docker compose run --rm cypress

scan: scan-api scan-front 
scan-api:
	docker compose run --rm trivy image --format table paper-identity/api:latest
scan-front:
	docker compose run --rm trivy image --format table paper-identity/front:latest

export ACTIVE_SCAN ?= true
export ACTIVE_SCAN_TIMEOUT ?= 600
export SERVICE_NAME ?= PaperIdentity
export SCAN_URL ?= http://front-web
cypress-zap:
	docker compose -f docker-compose.yml -f zap/docker-compose.zap.yml run --rm cypress
	docker compose -f docker-compose.yml -f zap/docker-compose.zap.yml exec -u root zap-proxy bash -c "apk add --no-cache jq"
	docker compose -f docker-compose.yml -f zap/docker-compose.zap.yml exec zap-proxy bash -c "/zap/wrk/scan.sh"
	docker compose -f docker-compose.yml -f zap/docker-compose.zap.yml down
