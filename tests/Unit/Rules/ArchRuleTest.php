<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ArchRuleTest extends TestCase
{
    public function test_a_class_matching_the_rule_is_checked_when_nothing_is_excluded(): void
    {
        self::assertEquals(1, $this->violationsOfARuleExcluding(null)->count());
    }

    /**
     * @dataProvider provideExcludedNamespaces
     */
    public function test_an_excluded_class_is_not_checked(string $excluded): void
    {
        self::assertEquals(0, $this->violationsOfARuleExcluding($excluded)->count());
    }

    public static function provideExcludedNamespaces(): array
    {
        return [
            'the class itself' => ['App\Billing\Legacy\Invoice'],
            'the namespace it sits in' => ['App\Billing\Legacy'],
            'a namespace above it' => ['App\Billing'],
            'a namespace with a wildcard' => ['App\*\Legacy'],
            'a namespace with a trailing wildcard' => ['App\*\Legacy\*'],
        ];
    }

    /**
     * @dataProvider provideNamespacesTheClassIsNotIn
     */
    public function test_a_class_outside_the_exclusion_is_still_checked(string $excluded): void
    {
        self::assertEquals(1, $this->violationsOfARuleExcluding($excluded)->count());
    }

    public static function provideNamespacesTheClassIsNotIn(): array
    {
        return [
            'another namespace' => ['App\Shipping'],
            'a longer name than the one it sits in' => ['App\Billing\LegacySupport'],
            'a wildcard on a longer name' => ['App\*\LegacySupport'],
            'a sibling of the class' => ['App\Billing\Legacy\Receipt'],
        ];
    }

    /** the class under test is App\Billing\Legacy\Invoice, and it is not final */
    private function violationsOfARuleExcluding(?string $excluded): Violations
    {
        $builder = Rule::allClasses();

        if (null !== $excluded) {
            $builder = $builder->except($excluded);
        }

        $rule = $builder
            ->that(new ResideInOneOfTheseNamespaces('App'))
            ->should(new IsFinal())
            ->because('an excluded class must not be checked at all');

        $violations = new Violations();
        $rule->check(ClassDescription::getBuilder('App\Billing\Legacy\Invoice', 'Invoice.php')->build(), $violations);

        return $violations;
    }
}
