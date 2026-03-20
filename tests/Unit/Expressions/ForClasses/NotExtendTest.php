<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotExtend;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotExtendTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $notExtend = new NotExtend(AbstractController::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ProductController::class)
            ->addExtends(AbstractController::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should not extend one of these classes: ' . AbstractController::class . ' because we want to add this rule for our software',
            $violationError
        );
    }

    public function test_it_should_not_return_violation_error_if_extends_another_class(): void
    {
        $notExtend = new NotExtend(AbstractController::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ProductController::class)
            ->addExtends(AbstractService::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_error_for_multiple_extends(): void
    {
        $notExtend = new NotExtend(AbstractController::class, AbstractService::class);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName(ProductService::class)
            ->addExtends(AbstractService::class, 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should not extend one of these classes: ' . AbstractController::class . ', ' . AbstractService::class . ' because we want to add this rule for our software',
            $violationError
        );
    }
}

// Fixtures

class AbstractController
{
}

class AbstractService
{
}

class ProductController extends AbstractController
{
}

class ProductService extends AbstractService
{
}
