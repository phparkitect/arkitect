<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\HaveAttribute;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class HaveAttributeTest extends TestCase
{
    public function test_it_should_return_true_if_contains_doc_block(): void
    {
        $expression = new HaveAttribute('myAttribute');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')->setClassName('HappyIsland\Myclass')
            ->addAttribute('myAttribute', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should have the attribute myAttribute because we want to add this rule for our software',
            $expression->describe($classDescription, $because)->toString()
        );
    }

    public function test_it_should_return_true_if_contains_doc_block_without_because(): void
    {
        $expression = new HaveAttribute('myAttribute');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')->setClassName('HappyIsland\Myclass')
            ->addAttribute('myAttribute', 1)
            ->build();

        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, '');

        self::assertEquals(0, $violations->count());
        self::assertEquals(
            'should have the attribute myAttribute',
            $expression->describe($classDescription, '')->toString()
        );
    }

    public function test_it_should_return_false_if_not_contains_doc_block(): void
    {
        $expression = new HaveAttribute('anotherAttribute');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')->setClassName('HappyIsland\Myclass')
            ->addAttribute('myAttribute', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }
}
