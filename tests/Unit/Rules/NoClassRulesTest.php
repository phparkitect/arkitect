<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\FileParserFactory;
use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\NotResideInTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Rule;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NoClassRulesTest extends TestCase
{
    public function test_no_class_without_that_clause_dsl_works(): void
    {
        $rule = Rule::noClass()
            ->should(new NotResideInTheseNamespaces('App\Services'))
            ->because('this namespace has been deprecated in favor of the modular architecture');

        $classSet = ClassSet::fromDir(__DIR__.'/../../E2E/_fixtures/mvc');

        $runner = new Runner();

        $runner->check(
            ClassSetRules::create($classSet, $rule),
            new VoidProgress(),
            FileParserFactory::createFileParser(TargetPhpVersion::create()),
            $violations = new Violations(),
            new ParsingErrors()
        );

        self::assertNotEmpty($violations->toArray());
    }

    public function test_no_class_dsl_works(): void
    {
        $rule = Rule::noClass()
            ->that(new ResideInOneOfTheseNamespaces('App\Entity'))
            ->should(new HaveNameMatching('*Service'))
            ->because('of our naming convention');

        $classSet = ClassSet::fromDir(__DIR__.'/../../E2E/_fixtures/mvc');

        $runner = new Runner();

        $runner->check(
            ClassSetRules::create($classSet, $rule),
            new VoidProgress(),
            FileParserFactory::createFileParser(TargetPhpVersion::create()),
            $violations = new Violations(),
            new ParsingErrors()
        );

        self::assertEmpty($violations->toArray());
    }
}
