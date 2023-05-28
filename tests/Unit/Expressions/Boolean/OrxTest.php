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
    public function test_it_should_return_no_violation_when_empty(): void
    {
        $or = new Orx();

        $classDescription = (new ClassDescriptionBuilder())
            ->setClassName('My\Class')
            ->setExtends('My\BaseClass', 10)
            ->build();

        $violations = new Violations();
        $or->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_no_violation_on_success(): void
    {
        $or = new Orx(new Extend('My\BaseClass'), new Extend('Your\OtherClass'));

        $classDescription = (new ClassDescriptionBuilder())
            ->setClassName('My\Class')
            ->setExtends('My\BaseClass', 10)
            ->build();

        $violations = new Violations();
        $or->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }
}
