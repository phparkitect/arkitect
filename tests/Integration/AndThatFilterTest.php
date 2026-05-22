<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration\PHPUnit;

use Arkitect\Expression\ForClasses\HaveAttribute;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\IsNotAbstract;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class AndThatFilterTest extends TestCase
{
    public function test_only_classes_matching_both_namespace_and_naming_are_checked(): void
    {
        // Rule: classes in App\Domain AND named *Event must be final.
        //
        // UserCreatedEvent  → matches both → not final → 1 violation
        // AbstractEvent     → matches namespace but IS abstract, not matching IsNotAbstract if used;
        //                     here it matches both conditions but satisfies IsFinal → 0 violations
        // OrderService      → matches namespace, does NOT match *Event → not checked → 0 violations
        // InfrastructureEvent → NOT in App\Domain → not checked → 0 violations
        $dir = vfsStream::setup('root', null, $this->createNamespaceAndNamingStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
            ->andThat(new HaveNameMatching('*Event'))
            ->should(new IsFinal())
            ->because('domain events must be final');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());
        self::assertEquals('App\Domain\UserCreatedEvent', $runner->getViolations()->get(0)->getFqcn());
    }

    public function test_classes_matching_namespace_and_attribute_but_failing_should_produce_violation(): void
    {
        // Rule: classes in App\Controller AND having #[AsController] must be final.
        //
        // ProductsController (#[AsController], not final) → matches both → violation
        // LegacyController   (#[AsController], final)     → matches both → no violation
        // UtilityHelper      (no attribute)               → fails andThat → not checked
        $dir = vfsStream::setup('root', null, $this->createNamespaceAndAttributeStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->andThat(new HaveAttribute('AsController'))
            ->should(new IsFinal())
            ->because('active controllers must be final');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());
        self::assertEquals('App\Controller\ProductsController', $runner->getViolations()->get(0)->getFqcn());
    }

    public function test_three_chained_and_that_conditions_all_must_match(): void
    {
        // Rule: classes in App\Domain AND named *Event AND non-abstract must be final.
        //
        // UserCreatedEvent  → matches all three → not final → violation
        // AbstractBaseEvent → matches namespace + *Event but IS abstract → third fails → not checked
        // OrderService      → matches namespace + IsNotAbstract but not *Event → not checked
        $dir = vfsStream::setup('root', null, $this->createThreeConditionStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
            ->andThat(new HaveNameMatching('*Event'))
            ->andThat(new IsNotAbstract())
            ->should(new IsFinal())
            ->because('concrete domain events must be final');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());
        self::assertEquals('App\Domain\UserCreatedEvent', $runner->getViolations()->get(0)->getFqcn());
    }

    public function test_except_exclusion_is_respected_with_and_that(): void
    {
        // Same rule as above, but UserCreatedEvent is excluded via except().
        // No violations expected.
        $dir = vfsStream::setup('root', null, $this->createNamespaceAndNamingStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->except('App\Domain\UserCreatedEvent')
            ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
            ->andThat(new HaveNameMatching('*Event'))
            ->should(new IsFinal())
            ->because('domain events must be final');

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());
    }

    // -------------------------------------------------------------------------
    // Directory structures
    // -------------------------------------------------------------------------

    private function createNamespaceAndNamingStructure(): array
    {
        return [
            'Domain' => [
                'UserCreatedEvent.php' => <<<'EOT'
                    <?php
                    namespace App\Domain;
                    class UserCreatedEvent {}
                    EOT,
                'OrderFinalizedEvent.php' => <<<'EOT'
                    <?php
                    namespace App\Domain;
                    final class OrderFinalizedEvent {}
                    EOT,
                'OrderService.php' => <<<'EOT'
                    <?php
                    namespace App\Domain;
                    class OrderService {}
                    EOT,
            ],
            'Infrastructure' => [
                'InfrastructureEvent.php' => <<<'EOT'
                    <?php
                    namespace App\Infrastructure;
                    class InfrastructureEvent {}
                    EOT,
            ],
        ];
    }

    private function createNamespaceAndAttributeStructure(): array
    {
        return [
            'Controller' => [
                'ProductsController.php' => <<<'EOT'
                    <?php
                    namespace App\Controller;
                    #[\AsController]
                    class ProductsController {}
                    EOT,
                'LegacyController.php' => <<<'EOT'
                    <?php
                    namespace App\Controller;
                    #[\Deprecated]
                    #[\AsController]
                    final class LegacyController {}
                    EOT,
                'UtilityHelper.php' => <<<'EOT'
                    <?php
                    namespace App\Controller;
                    class UtilityHelper {}
                    EOT,
            ],
        ];
    }

    private function createThreeConditionStructure(): array
    {
        return [
            'Domain' => [
                'UserCreatedEvent.php' => <<<'EOT'
                    <?php
                    namespace App\Domain;
                    class UserCreatedEvent {}
                    EOT,
                'AbstractBaseEvent.php' => <<<'EOT'
                    <?php
                    namespace App\Domain;
                    abstract class AbstractBaseEvent {}
                    EOT,
                'OrderService.php' => <<<'EOT'
                    <?php
                    namespace App\Domain;
                    class OrderService {}
                    EOT,
            ],
        ];
    }
}
