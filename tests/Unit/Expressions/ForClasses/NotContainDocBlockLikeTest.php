<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotContainDocBlockLike;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotContainDocBlockLikeTest extends TestCase
{
    public function test_it_should_return_true_if_not_contains_doc_block(): void
    {
        $expression = new NotContainDocBlockLike('anotherDocBlock');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addDocBlock('/**  */myDocBlock with other information')
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should not have a doc block that contains anotherDocBlock because we want to add this rule for our software',
            $expression->describe($classDescription, $because)->toString()
        );
    }

    public function test_it_should_return_false_if_contains_doc_block_without_because(): void
    {
        $expression = new NotContainDocBlockLike('anotherDocBlock');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addDocBlock('/**  */myDocBlock with other information')
            ->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should not have a doc block that contains anotherDocBlock',
            $expression->describe($classDescription, '')->toString()
        );
    }

    public function test_it_should_return_false_if_contains_doc_block(): void
    {
        $expression = new NotContainDocBlockLike('myDocBlock');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addDocBlock('/**  */myDocBlock with other information')
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }
}
