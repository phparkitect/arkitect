<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\IsA;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

/**
 * This test demonstrates the issue with IsA when classes are not autoloaded.
 *
 * The problem: IsA uses is_a() which requires classes to be loaded at runtime.
 * This works with composer install but fails with Docker/PHAR when analyzing
 * external code without a configured autoloader.
 *
 * The solution: IsA should use the parsed AST information from ClassDescription
 * (getExtends() and getInterfaces()) instead of is_a(), like Extend and Implement do.
 */
final class IsAWithoutAutoloadTest extends TestCase
{
    /**
     * This test uses fictional classes that don't exist in the autoloader.
     * It should pass because ClassDescription contains the parsed information
     * that the class extends the required parent, but it FAILS with current
     * implementation because is_a() can't find the classes at runtime.
     */
    public function test_it_should_work_with_extends_without_autoload(): void
    {
        // These are fictional classes that DON'T exist and aren't autoloadable
        $parentClass = 'App\\NonExistent\\BaseController';
        $childClass = 'App\\NonExistent\\UserController';

        $isA = new IsA($parentClass);

        // ClassDescription contains parsed AST info showing UserController extends BaseController
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/NonExistent/UserController.php')
            ->setClassName($childClass)
            ->addExtends($parentClass, 10)
            ->build();

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        // This SHOULD pass (no violations) because ClassDescription shows the extends relationship
        // But it FAILS with current is_a() implementation because classes aren't autoloaded
        self::assertEquals(
            0,
            $violations->count(),
            'IsA should work using parsed AST info without requiring classes to be autoloaded'
        );
    }

    /**
     * Same test but for interfaces.
     */
    public function test_it_should_work_with_implements_without_autoload(): void
    {
        // Fictional interface and class that don't exist
        $interface = 'App\\NonExistent\\RepositoryInterface';
        $className = 'App\\NonExistent\\UserRepository';

        $isA = new IsA($interface);

        // ClassDescription shows UserRepository implements RepositoryInterface
        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/NonExistent/UserRepository.php')
            ->setClassName($className)
            ->addInterface($interface, 10)
            ->build();

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        // This SHOULD pass but FAILS because is_a() requires autoloaded classes
        self::assertEquals(
            0,
            $violations->count(),
            'IsA should work using parsed AST info for interfaces without autoload'
        );
    }

    /**
     * This should correctly create a violation (class doesn't extend/implement required type)
     */
    public function test_it_should_create_violation_when_not_matching(): void
    {
        $requiredClass = 'App\\NonExistent\\BaseController';
        $actualClass = 'App\\NonExistent\\SomethingElse';

        $isA = new IsA($requiredClass);

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/NonExistent/SomethingElse.php')
            ->setClassName($actualClass)
            // No extends or implements added
            ->build();

        $violations = new Violations();
        $isA->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
    }
}
