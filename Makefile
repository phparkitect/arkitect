.PHONY: test build db dt dbi dphar csfix
.DEFAULT_GOAL := help

help: ## visualizza questo help
	@awk 'BEGIN {FS = ":.*#"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z0-9_-]+:.*?#/ { printf "  \033[36m%-27s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

db: ## lancia la build usando un container
	docker run --rm -it -v $(PWD):/var/www arkitect_php make build

dt: ## lancia i test usando un container
	docker run --rm -it -v $(PWD):/var/www arkitect_php make test

dbi: ## crea immagine docker per lo sviluppo
	docker image build -t arkitect_php .

dphar: ## crea un phar nel container
	docker run --rm -it -v $(PWD):/var/www arkitect_php make phar

shell: ## entra nel container
	docker run --rm -it -v $(PWD):/var/www arkitect_php bash

test: ## lancia i test
	bin/phpunit

test_%: ## lancia un test
	docker run --rm -it -v $(PWD):/var/www arkitect_php bin/phpunit --filter $@

%Test: ## lancia un test
	docker run --rm -it -v $(PWD):/var/www arkitect_php bin/phpunit --filter $@

phar: ## crea il phar
	rm -rf /tmp/arkitect && mkdir -p /tmp/arkitect
	cp -R src bin-stub box.json README.md composer.json composer.lock /tmp/arkitect
	cd /tmp/arkitect && composer install --prefer-source --no-dev -o
	bin/box build -c /tmp/arkitect/box.json
	cp /tmp/arkitect/phparkitect.phar .

outdated:
	composer outdated

coverage: ## lancia i test con coverage
	phpdbg -qrr ./bin/phpunit --coverage-html build/coverage

csfix: ## cs fix
	bin/php-cs-fixer fix -v

psalm: ## lancia psalm
	bin/psalm

build: ## lancia tutta la build
	composer install
	bin/php-cs-fixer fix -v
	#bin/psalm
	bin/phpunit

