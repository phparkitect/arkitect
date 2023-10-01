<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\RuleBuilders\Architecture;

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\Boolean\Andx;
use Arkitect\Expression\ForClasses\NotResideInTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\RuleBuilders\Architecture\Architecture;
use Arkitect\Tests\Unit\AbstractUnitTest;

class ArchitectureTest extends AbstractUnitTest
{
    public function test_it_should_see_violations_only_outside_exclusions(): void
    {
        $rules = Architecture::withComponents()
            ->component('ComponentA')->definedBy('Arkitect\Tests\Fixtures\ComponentA\\')
            ->component('ComponentB')->definedBy('Arkitect\Tests\Fixtures\ComponentB\\')
            ->component('ComponentC')->definedByExpression(
                new Andx(
                    new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\ComponentC\\'),
                    new NotResideInTheseNamespaces('Arkitect\Tests\Fixtures\ComponentC\ComponentCA\\')
                )
            )
            ->where('ComponentA')->mustNotDependOnComponents('ComponentB', 'ComponentC')
            ->where('ComponentB')->mustNotDependOnComponents('ComponentA', 'ComponentC')
            ->where('ComponentC')->mustNotDependOnComponents('ComponentA', 'ComponentB')
            ->rules('components should not directly depend on each other.');
        $config = new Config();
        $config->add(ClassSet::fromDir(\FIXTURES_PATH), ...iterator_to_array($rules));

        $runner = new Runner();
        $runner->run($config, new VoidProgress(), TargetPhpVersion::create());
        $violations = $runner->getViolations();

        self::assertEquals(1, $violations->count(), $violations->toString());
        $violationsText = $violations->toString();
        self::assertStringContainsString(
            'Arkitect\Tests\Fixtures\ComponentB\ClassBDependingOnAD has 1 violations',
            $violationsText
        );
        self::assertStringContainsString(
            "The dependency 'Arkitect\Tests\Fixtures\ComponentA\ClassAWithoutDependencies' violated the expression:",
            $violationsText
        );
        self::assertStringContainsString(
            'NOT resides in one of these namespaces: Arkitect\Tests\Fixtures\ComponentA\\',
            $violationsText
        );
    }
}
