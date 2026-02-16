.PHONY: test build db dt dbi dphar csfix
.DEFAULT_GOAL := help

TMP_DIR = /tmp/arkitect

help: ## it shows help menu
	@awk 'BEGIN {FS = ":.*#"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z0-9_-]+:.*?#/ { printf "  \033[36m%-27s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

dbi: ## it creates docker image
	docker image build -t phparkitect .

shell: ## it enters into the container
	docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect bash

test: ## it launches tests
	bin/phpunit

test_%: ## it launches a test
	bin/phpunit --filter $@

%Test: ## it launches a test
	bin/phpunit --filter $@

phar: ## it creates phar
	rm -rf ${TMP_DIR} && mkdir -p ${TMP_DIR}
	cp -R src bin-stub box.json README.md composer.json phparkitect-stub.php bin ${TMP_DIR}
	cd ${TMP_DIR} && composer install --prefer-source --no-dev -o
	bin/box.phar compile -c ${TMP_DIR}/box.json
	cp ${TMP_DIR}/phparkitect.phar .

outdated:
	composer outdated


csfix: ## it launches cs fix
	PHP_CS_FIXER_IGNORE_ENV=1 bin/php-cs-fixer fix -v

psalm: ## it launches psalm
	bin/psalm.phar --no-cache

build: ## it launches all the build
	composer install
	PHP_CS_FIXER_IGNORE_ENV=1 bin/php-cs-fixer fix -v
	bin/psalm.phar --no-cache
	bin/phpunit

sfbuild: ## it launches all the build
	symfony php composer.phar install
	PHP_CS_FIXER_IGNORE_ENV=1 symfony php bin/php-cs-fixer fix -v
	symfony php bin/psalm
	symfony php bin/phpunit

dt: ##it launches tests using container
	docker run --rm -it --entrypoint= -v $(PWD):/arkitect phparkitect make test

