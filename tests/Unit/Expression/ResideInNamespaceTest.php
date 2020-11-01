<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expression;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ResideInNamespace;
use PHPUnit\Framework\TestCase;

class ResideInNamespaceTest extends TestCase
{
    public function test_class_not_in_namespace(): void
    {
        $exp = new ResideInNamespace('App\Controller');

        $class = new ClassDescription(
            __FILE__,
            FullyQualifiedClassName::fromString(self::class),
            [],
            []
        );

        self::assertFalse($exp($class));
    }

    public function test_class_in_namespace(): void
    {
        $exp = new ResideInNamespace('App\Controller');

        $class = new ClassDescription(
            __FILE__,
            FullyQualifiedClassName::fromString('App\Controller\LoginController'),
            [],
            []
        );

        self::assertTrue($exp($class));
    }
}
