<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Constraint;

use Arkitect\Evaluate\Constraint\NotDependOnTheseClasses;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class NotDependOnTheseClassesTest extends TestCase
{
    public function test_a_dependency_on_a_named_class_is_a_violation_at_its_line(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order', dependencies: ['App\Infra\Db' => 7]);

        $violations = (new NotDependOnTheseClasses(['App\Infra\Db']))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertSame([7], array_map(static fn ($v) => $v->line, iterator_to_array($violations)));
    }

    public function test_a_class_beneath_a_named_one_is_not_that_class(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order', dependencies: ['App\Infra\Db\Connection' => 7]);

        self::assertCount(0, (new NotDependOnTheseClasses(['App\Infra\Db']))->evaluate($class, new ParsedClassGraph())->violations);
    }
}
