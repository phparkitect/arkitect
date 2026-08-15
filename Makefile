.PHONY: test lint fix run all

# there is no phpunit.xml: the suite path has to be given
test:
	vendor/bin/phpunit tests

lint:
	vendor/bin/php-cs-fixer fix --dry-run --diff

fix:
	vendor/bin/php-cs-fixer fix

# arkitect checking its own architecture; exits 1 on violations
run:
	php run.php

all: fix test run
