.PHONY: test build db dt dbi

db:
	docker run --rm -it -v $(PWD):/var/www arkitect_php make build

dt:
	docker run --rm -it -v $(PWD):/var/www arkitect_php make test

dbi:
	docker image build -t arkitect_php:1.0 .

test:
	vendor/bin/phpunit

build:
	composer install
	php-cs-fixer fix --dry-run
	vendor/bin/phpunit

shell:
	 docker-compose exec php /bin/bash