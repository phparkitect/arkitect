<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\IsMapped;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

final class IsMappedTest extends TestCase
{
    public function test_it_should_not_add_violation_if_fqcn_is_in_list(): void
    {
        $listedFqcn = 'App\SharedKernel\Component\MyClass';
        $list = [
            'a' => $listedFqcn,
            'b' => 'App\SharedKernel\Component\MyOtherClass',
        ];
        $expression = new IsMapped($list);
        $classDescription = (new ClassDescriptionBuilder())->setClassName($listedFqcn)->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_add_violation_if_fqcn_is_not_in_list(): void
    {
        $nonListedFqcn = 'App\SharedKernel\Component\MyClass';
        $list = [
            'a' => 'App\SharedKernel\Component\SomeClass',
            'b' => 'App\SharedKernel\Component\MyOtherClass',
        ];
        $expression = new IsMapped($list);
        $classDescription = (new ClassDescriptionBuilder())->setClassName($nonListedFqcn)->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, '');

        self::assertEquals(1, $violations->count());
        self::assertEquals(IsMapped::POSITIVE_DESCRIPTION, $expression->describe($classDescription, '')->toString());
    }
}
