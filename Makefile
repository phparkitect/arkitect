.PHONY: test build db dt dbi csfix

db:
	docker run --rm -it -v $(PWD):/var/www arkitect_php make build

dt:
	docker run --rm -it -v $(PWD):/var/www arkitect_php make test

dbi:
	docker image build -t arkitect_php:1.0 .

test:
	bin/phpunit

csfix:
	bin/php-cs-fixer fix -v

psalm:
	bin/psalm

build:
	composer install
	bin/php-cs-fixer fix --dry-run
	bin/phpunit

shell:
	 docker-compose exec php /bin/bash

coverage:
	bin/phpunit --coverage-html web/tests

