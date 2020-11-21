.PHONY: test build db dt dbi csfix
.DEFAULT_GOAL := help

help: ## visualizza questo help
	@awk 'BEGIN {FS = ":.*#"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z0-9_-]+:.*?#/ { printf "  \033[36m%-27s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

db: ## lancia la build usando un container
	docker run --rm -it -v $(PWD):/var/www arkitect_php make build

dt: ## lancia i test usando un container
	docker run --rm -it -v $(PWD):/var/www arkitect_php make test

dbi: ## crea l'immagine docker per lo sviluppo
	docker image build -t arkitect_php:1.0 .

shell: ## entra nel container
	 docker-compose exec php /bin/bash

test: ## lancia i test
	bin/phpunit

coverage: ## lancia i test con coverage
	bin/phpunit --coverage-html web/tests

csfix: ## cs fix
	bin/php-cs-fixer fix -v

psalm: ## lancia psalm
	bin/psalm

build: ## laacia tutta la build
	composer install
	bin/php-cs-fixer fix --dry-run
	bin/psalm
	bin/phpunit

