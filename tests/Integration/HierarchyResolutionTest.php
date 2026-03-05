<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Analyzer\ClassHierarchyResolver;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\ClassSet;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\HaveTrait;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\NotExtend;
use Arkitect\Expression\ForClasses\NotHaveTrait;
use Arkitect\Expression\ForClasses\NotImplement;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Rule;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

/**
 * Tests that BetterReflection-based hierarchy resolution works correctly.
 *
 * ChildClass extends ParentClass (which implements BaseInterface and uses BaseTrait).
 * ChildClass does NOT directly declare the interface or the trait — it inherits them.
 * Without hierarchy resolution, these checks would fail. With it, they pass.
 */
class HierarchyResolutionTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__.'/Fixtures/Hierarchy';
    }

    public function test_child_class_extends_parent_class(): void
    {
        $rule = Rule::allClasses()
            ->that(new Extend('Arkitect\Tests\Fixtures\Hierarchy\ParentClass'))
            ->should(new Implement('Arkitect\Tests\Fixtures\Hierarchy\BaseInterface'))
            ->because('classes extending ParentClass inherit BaseInterface');

        $violations = $this->runRule($rule);

        self::assertCount(0, $violations, 'ChildClass should be seen as implementing BaseInterface through ParentClass');
    }

    public function test_child_class_inherits_trait_from_parent(): void
    {
        $rule = Rule::allClasses()
            ->that(new Extend('Arkitect\Tests\Fixtures\Hierarchy\ParentClass'))
            ->should(new HaveTrait('Arkitect\Tests\Fixtures\Hierarchy\BaseTrait'))
            ->because('classes extending ParentClass inherit BaseTrait');

        $violations = $this->runRule($rule);

        self::assertCount(0, $violations, 'ChildClass should be seen as using BaseTrait through ParentClass');
    }

    public function test_child_class_full_extends_chain(): void
    {
        $rule = Rule::allClasses()
            ->that(new Implement('Arkitect\Tests\Fixtures\Hierarchy\BaseInterface'))
            ->should(new HaveTrait('Arkitect\Tests\Fixtures\Hierarchy\BaseTrait'))
            ->because('all implementors of BaseInterface should use BaseTrait');

        $violations = $this->runRule($rule);

        // Both ParentClass and ChildClass implement BaseInterface (ChildClass through inheritance)
        // Both also have BaseTrait (ChildClass through inheritance)
        self::assertCount(0, $violations);
    }

    public function test_not_extend_detects_inherited_parent(): void
    {
        $rule = Rule::allClasses()
            ->that(new Implement('Arkitect\Tests\Fixtures\Hierarchy\BaseInterface'))
            ->should(new NotExtend('Arkitect\Tests\Fixtures\Hierarchy\ParentClass'))
            ->because('test');

        $violations = $this->runRule($rule);

        // ChildClass implements BaseInterface (inherited) and extends ParentClass → violation
        self::assertCount(1, $violations);
        self::assertEquals('Arkitect\Tests\Fixtures\Hierarchy\ChildClass', $violations->get(0)->getFqcn());
    }

    public function test_not_implement_detects_inherited_interface(): void
    {
        $rule = Rule::allClasses()
            ->that(new Extend('Arkitect\Tests\Fixtures\Hierarchy\ParentClass'))
            ->should(new NotImplement('Arkitect\Tests\Fixtures\Hierarchy\BaseInterface'))
            ->because('test');

        $violations = $this->runRule($rule);

        // ChildClass extends ParentClass and inherits BaseInterface → violation
        self::assertCount(1, $violations);
        self::assertEquals('Arkitect\Tests\Fixtures\Hierarchy\ChildClass', $violations->get(0)->getFqcn());
    }

    public function test_not_have_trait_detects_inherited_trait(): void
    {
        $rule = Rule::allClasses()
            ->that(new Extend('Arkitect\Tests\Fixtures\Hierarchy\ParentClass'))
            ->should(new NotHaveTrait('Arkitect\Tests\Fixtures\Hierarchy\BaseTrait'))
            ->because('test');

        $violations = $this->runRule($rule);

        // ChildClass extends ParentClass and inherits BaseTrait → violation
        self::assertCount(1, $violations);
        self::assertEquals('Arkitect\Tests\Fixtures\Hierarchy\ChildClass', $violations->get(0)->getFqcn());
    }

    private function runRule(ArchRule $rule): Violations
    {
        $resolver = new ClassHierarchyResolver([$this->fixturesDir]);
        $fileParser = FileParserFactory::createFileParser(
            TargetPhpVersion::create('8.2'),
            true,
            $resolver
        );

        $violations = new Violations();

        foreach (ClassSet::fromDir($this->fixturesDir) as $file) {
            $fileParser->parse($file->getContents(), $file->getRelativePathname());

            foreach ($fileParser->getClassDescriptions() as $classDescription) {
                $rule->check($classDescription, $violations);
            }
        }

        return $violations;
    }
}
