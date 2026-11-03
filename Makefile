.PHONY: test lint fix psalm run all

# there is no phpunit.xml: the suite path has to be given
test:
	vendor/bin/phpunit tests

lint:
	vendor/bin/php-cs-fixer fix --dry-run --diff

fix:
	vendor/bin/php-cs-fixer fix

# psalm lives in tools/ so it is not a dependency of the library
psalm:
	tools/psalm/vendor/bin/psalm

# arkitect checking its own architecture; exits 1 on violations.
# everything under the root is parsed, tools/psalm/vendor included, so the
# default limit is not enough once psalm is installed
run:
	php -d memory_limit=512M run.php

all: fix psalm test run
