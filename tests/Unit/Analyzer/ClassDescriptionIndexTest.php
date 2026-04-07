<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Analyzer\ClassDescriptionIndex;
use Arkitect\Analyzer\ClassDescriptions;
use Arkitect\Analyzer\ParserResult;
use PHPUnit\Framework\TestCase;

class ClassDescriptionIndexTest extends TestCase
{
    public function test_class_with_no_extension_points_keeps_its_own_deps(): void
    {
        $cd = (new ClassDescriptionBuilder())
            ->setFilePath('src/A.php')
            ->setClassName('App\A')
            ->addDependency(new ClassDependency('App\Dep', 1))
            ->build();

        $index = $this->parsedFilesFrom(['src/A.php' => [$cd]]);

        $enriched = $index->get('App\A');
        self::assertNotNull($enriched);
        self::assertCount(1, $enriched->getDependencies());
        self::assertEquals('App\Dep', $enriched->getDependencies()[0]->getFQCN()->toString());
    }

    public function test_class_inherits_deps_from_parent(): void
    {
        $cdA = (new ClassDescriptionBuilder())
            ->setFilePath('src/A.php')
            ->setClassName('App\A')
            ->addExtends('App\B', 1)
            ->build();

        $cdB = (new ClassDescriptionBuilder())
            ->setFilePath('src/B.php')
            ->setClassName('App\B')
            ->addDependency(new ClassDependency('App\C', 1))
            ->build();

        $index = $this->parsedFilesFrom([
            'src/A.php' => [$cdA],
            'src/B.php' => [$cdB],
        ]);

        $enriched = $index->get('App\A');
        self::assertNotNull($enriched);

        $fqcns = $this->depFqcns($enriched->getDependencies());
        self::assertContains('App\B', $fqcns);
        self::assertContains('App\C', $fqcns);
    }

    public function test_interface_chain_is_resolved_transitively(): void
    {
        // A implements B, B extends C, C has its own dep D
        $cdA = (new ClassDescriptionBuilder())
            ->setFilePath('src/A.php')
            ->setClassName('App\A')
            ->addInterface('App\B', 1)
            ->build();

        $cdB = (new ClassDescriptionBuilder())
            ->setFilePath('src/B.php')
            ->setClassName('App\B')
            ->addInterface('App\C', 1)
            ->setInterface(true)
            ->build();

        $cdC = (new ClassDescriptionBuilder())
            ->setFilePath('src/C.php')
            ->setClassName('App\C')
            ->addDependency(new ClassDependency('App\D', 1))
            ->setInterface(true)
            ->build();

        $index = $this->parsedFilesFrom([
            'src/A.php' => [$cdA],
            'src/B.php' => [$cdB],
            'src/C.php' => [$cdC],
        ]);

        $enriched = $index->get('App\A');
        self::assertNotNull($enriched);

        $fqcns = $this->depFqcns($enriched->getDependencies());
        self::assertContains('App\B', $fqcns);
        self::assertContains('App\C', $fqcns);
        self::assertContains('App\D', $fqcns);
    }

    public function test_diamond_inheritance_does_not_duplicate_deps(): void
    {
        // A extends B and C, B extends D, C extends D — D's deps should appear once
        $cdA = (new ClassDescriptionBuilder())
            ->setFilePath('src/A.php')
            ->setClassName('App\A')
            ->addExtends('App\B', 1)
            ->addInterface('App\C', 2)
            ->build();

        $cdB = (new ClassDescriptionBuilder())
            ->setFilePath('src/B.php')
            ->setClassName('App\B')
            ->addExtends('App\D', 1)
            ->build();

        $cdC = (new ClassDescriptionBuilder())
            ->setFilePath('src/C.php')
            ->setClassName('App\C')
            ->addInterface('App\D', 1)
            ->setInterface(true)
            ->build();

        $cdD = (new ClassDescriptionBuilder())
            ->setFilePath('src/D.php')
            ->setClassName('App\D')
            ->addDependency(new ClassDependency('App\Shared', 1))
            ->build();

        $index = $this->parsedFilesFrom([
            'src/A.php' => [$cdA],
            'src/B.php' => [$cdB],
            'src/C.php' => [$cdC],
            'src/D.php' => [$cdD],
        ]);

        $enriched = $index->get('App\A');
        self::assertNotNull($enriched);

        $fqcns = $this->depFqcns($enriched->getDependencies());
        self::assertContains('App\Shared', $fqcns);
        self::assertCount(1, array_filter($fqcns, static fn (string $f): bool => 'App\Shared' === $f));
    }

    public function test_cycle_does_not_cause_infinite_loop(): void
    {
        $cdA = (new ClassDescriptionBuilder())
            ->setFilePath('src/A.php')
            ->setClassName('App\A')
            ->addInterface('App\B', 1)
            ->build();

        $cdB = (new ClassDescriptionBuilder())
            ->setFilePath('src/B.php')
            ->setClassName('App\B')
            ->addInterface('App\A', 1)
            ->setInterface(true)
            ->build();

        $index = $this->parsedFilesFrom([
            'src/A.php' => [$cdA],
            'src/B.php' => [$cdB],
        ]);

        // Must not throw or loop forever

        self::assertNotNull($index->get('App\A'));
        self::assertNotNull($index->get('App\B'));
    }

    // --- helpers ---

    private function parsedFilesFrom(array $map): ClassDescriptionIndex
    {
        $index = new ClassDescriptionIndex();

        foreach ($map as $path => $classDescriptions) {
            $index->add(
                $path,
                ParserResult::withClassDescriptions(new ClassDescriptions($classDescriptions))
            );
        }

        $index->enrich();

        return $index;
    }

    /** @return list<string> */
    private function depFqcns(array $deps): array
    {
        return array_values(array_map(
            static fn (ClassDependency $d): string => $d->getFQCN()->toString(),
            $deps
        ));
    }
}
