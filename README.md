# Arkitect [![Latest Stable Version](https://poser.pugx.org/phparkitect/phparkitect/v/stable)](https://packagist.org/packages/phparkitect/phparkitect)  ![Arkitect](https://github.com/phparkitect/arkitect/workflows/Arkitect/badge.svg?branch=master)

Arkitect helps you to keep your PHP codebase coherent and solid, by permitting to add some architectural costraint check to your workflow.
You can express the costraint that you want to enforce, in simple and readable PHP code, for example:

```php
Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
    ->should(new HaveNameMatching('*Controller'))
    ->because("it's a symfony naming convention");
```

You can check all the costraints using our cli tool, or, if you prefer, you can add the rules to your Phpunit tests, like this:

```php
 public function test_controller_naming_convention(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/fixtures/happy_island');

        Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new HaveNameMatching('*Controller'))
            ->because("it's a symfony naming convention");

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
```

## What kind of rules can I enforce with Arkitect

Currently you can check if a class:
 - implements an interface
 - have a name matching a pattern
 - depends on a namespace
 - don't have dependency outside a namespace 

# How to install

## Using composer
## Using a phar
## Using docker

If you would like to use Arkitect with Docker you can launch this command:

```
docker run --rm -it -v $(PWD):/project phparkitect/phparkitect:latest check --config=/project/yourConfigFile.php
```
If you have a project with an incompatible version of PHP with Arkitect, using Docker can help you use Arkitect despite the PHP version.

# How to use it
## With PHPUnit
## Standalone
