<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\ContainDocBlockLike;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Utils\MockHierarchyResolver;
use PHPUnit\Framework\TestCase;

class ContainDocBlockLikeTest extends TestCase
{
    use MockHierarchyResolver;

    public function test_it_should_return_true_if_contains_doc_block(): void
    {
        $expression = new ContainDocBlockLike('myDocBlock');

        $classDescription = ($this->createBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland\Myclass')
            ->addDocBlock('/**  */myDocBlock with other information')
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should have a doc block that contains myDocBlock because we want to add this rule for our software',
            $expression->describe($classDescription, $because)->toString()
        );
    }

    public function test_it_should_return_false_if_not_contains_doc_block(): void
    {
        $expression = new ContainDocBlockLike('anotherDocBlock');

        $classDescription = ($this->createBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland\Myclass')
            ->addDocBlock('/**  */myDocBlock with other information')
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }
}
