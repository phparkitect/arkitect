<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\Boolean;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\Boolean\Orx;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

final class OrxTest extends TestCase
{
    public function test_it_should_throw_exception_if_no_expressions_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Orx([]);
    }

    public function test_it_should_throw_exception_if_only_one_expression_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Orx([new Extend('My\BaseClass')]);
    }

    public function test_it_should_return_no_violation_on_success(): void
    {
        $or = new Orx([new Extend('My\BaseClass'), new Extend('Your\OtherClass')]);

        $classDescription = (new ClassDescriptionBuilder())
            ->setClassName('My\Class')
            ->setExtends('My\BaseClass', 10)
            ->build();

        $violations = new Violations();
        $or->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }
}
