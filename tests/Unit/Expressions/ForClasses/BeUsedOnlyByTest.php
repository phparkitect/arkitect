<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\BeUsedOnlyBy;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class BeUsedOnlyByTest extends TestCase
{
    protected function setUp(): void
    {
        BeUsedOnlyBy::clearUsageMap();
    }

    public function test_it_should_return_no_violations_if_not_used(): void
    {
        $expression = new BeUsedOnlyBy(['Acme\Service']);

        $classDescription = ClassDescription::getBuilder('Acme\Quoting\Request', 'src/Request.php')->build();

        $because = 'we want to protect our domain';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should be used only by classes in: Acme\Service because we want to protect our domain',
            $expression->describe($classDescription, $because)->toString()
        );
    }

    public function test_it_should_return_no_violations_if_used_by_allowed_namespace(): void
    {
        $expression = new BeUsedOnlyBy(['Acme\Service']);

        // Register the user class's dependencies (Acme\Service\Handler uses Acme\Quoting\Request)
        $userClass = ClassDescription::getBuilder('Acme\Service\Handler', 'src/Service/Handler.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 10))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($userClass);

        // Now evaluate the target class
        $targetClass = ClassDescription::getBuilder('Acme\Quoting\Request', 'src/Quoting/Request.php')->build();

        $violations = new Violations();
        $expression->evaluate($targetClass, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violations_if_used_by_non_allowed_namespace(): void
    {
        $expression = new BeUsedOnlyBy(['Acme\Service']);

        // Register the user class's dependencies (Acme\Controller\Api uses Acme\Quoting\Request)
        $userClass = ClassDescription::getBuilder('Acme\Controller\Api', 'src/Controller/Api.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 15))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($userClass);

        // Now evaluate the target class
        $targetClass = ClassDescription::getBuilder('Acme\Quoting\Request', 'src/Quoting/Request.php')->build();

        $violations = new Violations();
        $expression->evaluate($targetClass, $violations, '');

        self::assertEquals(1, $violations->count());
        self::assertStringContainsString('is used by Acme\Controller\Api', $violations->get(0)->getError());
    }

    public function test_it_should_return_violations_only_for_non_allowed_users(): void
    {
        $expression = new BeUsedOnlyBy(['Acme\Service', 'Acme\Domain']);

        // Register allowed user
        $allowedUser = ClassDescription::getBuilder('Acme\Service\Handler', 'src/Service/Handler.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 10))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($allowedUser);

        // Register another allowed user
        $anotherAllowedUser = ClassDescription::getBuilder('Acme\Domain\Logger', 'src/Domain/Logger.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 20))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($anotherAllowedUser);

        // Register non-allowed user
        $nonAllowedUser = ClassDescription::getBuilder('Acme\Controller\Api', 'src/Controller/Api.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 15))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($nonAllowedUser);

        // Now evaluate the target class
        $targetClass = ClassDescription::getBuilder('Acme\Quoting\Request', 'src/Quoting/Request.php')->build();

        $violations = new Violations();
        $expression->evaluate($targetClass, $violations, '');

        self::assertEquals(1, $violations->count());
        self::assertStringContainsString('is used by Acme\Controller\Api', $violations->get(0)->getError());
    }

    public function test_it_should_support_wildcard_in_allowed_namespaces(): void
    {
        $expression = new BeUsedOnlyBy(['Acme\Service\*']);

        // Register the user class's dependencies
        $userClass = ClassDescription::getBuilder('Acme\Service\SubModule\Handler', 'src/Service/SubModule/Handler.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 10))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($userClass);

        // Now evaluate the target class
        $targetClass = ClassDescription::getBuilder('Acme\Quoting\Request', 'src/Quoting/Request.php')->build();

        $violations = new Violations();
        $expression->evaluate($targetClass, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_report_multiple_violations_for_multiple_non_allowed_users(): void
    {
        $expression = new BeUsedOnlyBy(['Acme\Service']);

        // Register first non-allowed user
        $firstUser = ClassDescription::getBuilder('Acme\Controller\Api', 'src/Controller/Api.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 15))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($firstUser);

        // Register second non-allowed user
        $secondUser = ClassDescription::getBuilder('Acme\Utils\Helper', 'src/Utils/Helper.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 25))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($secondUser);

        // Now evaluate the target class
        $targetClass = ClassDescription::getBuilder('Acme\Quoting\Request', 'src/Quoting/Request.php')->build();

        $violations = new Violations();
        $expression->evaluate($targetClass, $violations, '');

        self::assertEquals(2, $violations->count());
    }

    public function test_it_should_clear_usage_map(): void
    {
        // Register some dependencies
        $userClass = ClassDescription::getBuilder('Acme\Controller\Api', 'src/Controller/Api.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 15))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($userClass);

        self::assertNotEmpty(BeUsedOnlyBy::getUsageMap());

        BeUsedOnlyBy::clearUsageMap();

        self::assertEmpty(BeUsedOnlyBy::getUsageMap());
    }

    public function test_it_should_match_exact_class_name_in_allowed(): void
    {
        $expression = new BeUsedOnlyBy(['Acme\Service\SpecificHandler']);

        // Register the allowed user
        $allowedUser = ClassDescription::getBuilder('Acme\Service\SpecificHandler', 'src/Service/SpecificHandler.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 10))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($allowedUser);

        // Register a non-allowed user (different class in same namespace)
        $nonAllowedUser = ClassDescription::getBuilder('Acme\Service\OtherHandler', 'src/Service/OtherHandler.php')
            ->addDependency(new ClassDependency('Acme\Quoting\Request', 20))
            ->build();
        BeUsedOnlyBy::registerClassDependencies($nonAllowedUser);

        // Now evaluate the target class
        $targetClass = ClassDescription::getBuilder('Acme\Quoting\Request', 'src/Quoting/Request.php')->build();

        $violations = new Violations();
        $expression->evaluate($targetClass, $violations, '');

        self::assertEquals(1, $violations->count());
        self::assertStringContainsString('is used by Acme\Service\OtherHandler', $violations->get(0)->getError());
    }
}
