<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\IsNotAbstract;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class AndThatShouldTest extends TestCase
{
    public function test_class_matching_all_conditions_is_checked_against_should_and_produces_violation(): void
    {
        // UserController is in App\Controller AND its name matches *Controller
        // but it is NOT final — should() will fire and produce a violation
        $class = ClassDescription::getBuilder('App\Controller\UserController', 'src/Controller/UserController.php')
            ->build();

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->andThat(new HaveNameMatching('*Controller'))
            ->should(new IsFinal())
            ->because('controllers must be final');

        $violations = new Violations();
        $rule->check($class, $violations);

        self::assertCount(1, $violations);
        self::assertStringContainsString('App\Controller\UserController', $violations->get(0)->getFqcn());
    }

    public function test_class_matching_all_conditions_and_satisfying_should_produces_no_violation(): void
    {
        $class = ClassDescription::getBuilder('App\Controller\UserController', 'src/Controller/UserController.php')
            ->setFinal(true)
            ->build();

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->andThat(new HaveNameMatching('*Controller'))
            ->should(new IsFinal())
            ->because('controllers must be final');

        $violations = new Violations();
        $rule->check($class, $violations);

        self::assertCount(0, $violations);
    }

    public function test_class_matching_only_first_condition_is_not_checked_against_should(): void
    {
        // UserService is in App\Controller but does NOT match *Controller
        // andThat() filters it out — should() must never fire
        $class = ClassDescription::getBuilder('App\Controller\UserService', 'src/Controller/UserService.php')
            ->build();

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->andThat(new HaveNameMatching('*Controller'))
            ->should(new IsFinal())
            ->because('controllers must be final');

        $violations = new Violations();
        $rule->check($class, $violations);

        self::assertCount(0, $violations);
    }

    public function test_class_matching_only_second_condition_is_not_checked_against_should(): void
    {
        // ProductController matches *Controller but is NOT in App\Controller
        $class = ClassDescription::getBuilder('App\Service\ProductController', 'src/Service/ProductController.php')
            ->build();

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->andThat(new HaveNameMatching('*Controller'))
            ->should(new IsFinal())
            ->because('controllers must be final');

        $violations = new Violations();
        $rule->check($class, $violations);

        self::assertCount(0, $violations);
    }

    public function test_class_matching_neither_condition_is_not_checked_against_should(): void
    {
        $class = ClassDescription::getBuilder('App\Service\UserService', 'src/Service/UserService.php')
            ->build();

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->andThat(new HaveNameMatching('*Controller'))
            ->should(new IsFinal())
            ->because('controllers must be final');

        $violations = new Violations();
        $rule->check($class, $violations);

        self::assertCount(0, $violations);
    }

    public function test_three_chained_and_that_all_match_produces_violation(): void
    {
        // Matches: App\Domain namespace, *Event name, non-abstract
        // should(IsFinal) fires — class is not final → violation
        $class = ClassDescription::getBuilder('App\Domain\UserCreatedEvent', 'src/Domain/UserCreatedEvent.php')
            ->build();

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
            ->andThat(new HaveNameMatching('*Event'))
            ->andThat(new IsNotAbstract())
            ->should(new IsFinal())
            ->because('domain events must be final');

        $violations = new Violations();
        $rule->check($class, $violations);

        self::assertCount(1, $violations);
    }

    public function test_three_chained_and_that_last_condition_not_matched_skips_should(): void
    {
        // Matches: App\Domain namespace, *Event name
        // Does NOT match IsNotAbstract (class IS abstract) → should() is skipped
        $class = ClassDescription::getBuilder('App\Domain\AbstractEvent', 'src/Domain/AbstractEvent.php')
            ->setAbstract(true)
            ->build();

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Domain'))
            ->andThat(new HaveNameMatching('*Event'))
            ->andThat(new IsNotAbstract())
            ->should(new IsFinal())
            ->because('domain events must be final');

        $violations = new Violations();
        $rule->check($class, $violations);

        self::assertCount(0, $violations);
    }

    public function test_reusing_base_rule_with_different_and_that_produces_independent_rules(): void
    {
        $base = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Domain'));

        $eventRule = $base
            ->andThat(new HaveNameMatching('*Event'))
            ->should(new IsFinal())
            ->because('domain events must be final');

        $serviceRule = $base
            ->andThat(new HaveNameMatching('*Service'))
            ->should(new IsNotAbstract())
            ->because('domain services must not be abstract');

        $eventClass = ClassDescription::getBuilder('App\Domain\UserCreatedEvent', 'src/Domain/UserCreatedEvent.php')
            ->build();

        $eventViolations = new Violations();
        $eventRule->check($eventClass, $eventViolations);

        $serviceViolations = new Violations();
        $serviceRule->check($eventClass, $serviceViolations);

        // eventRule fires (matches namespace + *Event, not final) → 1 violation
        self::assertCount(1, $eventViolations);

        // serviceRule does not fire (class name is *Event, not *Service) → 0 violations
        self::assertCount(0, $serviceViolations);
    }
}
