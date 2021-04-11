.PHONY: test build db dt dbi dphar csfix
.DEFAULT_GOAL := help

help: ## it shows help menu
	@awk 'BEGIN {FS = ":.*#"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z0-9_-]+:.*?#/ { printf "  \033[36m%-27s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

db: ## it launches build using container
	docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect make build

dt: ##it launches tests using container
	docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect make test

dbi: ## it creates docker image
	docker image build -t phparkitect .

dphar: ## it creates phar inside container
	docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect make phar

shell: ## it enters into the container
	docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect bash

test: ## it launches tests
	bin/phpunit -v

test_%: ## it launches a test
	docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect bin/phpunit --filter $@

%Test: ## lancia un test
	docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect bin/phpunit --filter $@

phar: ## it creates phar
	rm -rf /tmp/arkitect && mkdir -p /tmp/arkitect
	cp -R src bin-stub box.json README.md composer.json composer.lock /tmp/arkitect
	cd /tmp/arkitect && composer install --prefer-source --no-dev -o
	bin/box build -c /tmp/arkitect/box.json
	cp /tmp/arkitect/phparkitect.phar .

outdated:
	composer outdated

coverage: ## it launches coverage
	phpdbg -qrr ./bin/phpunit --coverage-html build/coverage

csfix: ## cs fix
	bin/php-cs-fixer fix -v

psalm: ## it launches psalm
	bin/psalm

build: ## it launches all the build
	composer install
	bin/php-cs-fixer fix -v
	#bin/psalm
	bin/phpunit

