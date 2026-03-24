<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptions;
use PHPUnit\Framework\TestCase;

class ClassDescriptionsTest extends TestCase
{
    private ClassDescriptions $classDescriptions;

    private ClassDescription $classDescription;

    protected function setUp(): void
    {
        $this->classDescription = ClassDescription::getBuilder('App\Foo', 'src/Foo.php')->build();
        $this->classDescriptions = new ClassDescriptions([$this->classDescription]);
    }

    public function test_offset_exists_returns_true_for_existing_index(): void
    {
        self::assertTrue(isset($this->classDescriptions[0]));
    }

    public function test_offset_exists_returns_false_for_missing_index(): void
    {
        self::assertFalse(isset($this->classDescriptions[99]));
    }

    public function test_offset_get_returns_element_at_index(): void
    {
        self::assertSame($this->classDescription, $this->classDescriptions[0]);
    }

    public function test_offset_set_appends_when_offset_is_null(): void
    {
        $second = ClassDescription::getBuilder('App\Bar', 'src/Bar.php')->build();
        $this->classDescriptions[] = $second;

        self::assertCount(2, $this->classDescriptions);
        self::assertSame($second, $this->classDescriptions[1]);
    }

    public function test_offset_set_assigns_to_specific_index(): void
    {
        $replacement = ClassDescription::getBuilder('App\Baz', 'src/Baz.php')->build();
        $this->classDescriptions[0] = $replacement;

        self::assertSame($replacement, $this->classDescriptions[0]);
    }

    public function test_offset_unset_removes_element(): void
    {
        unset($this->classDescriptions[0]);

        self::assertFalse(isset($this->classDescriptions[0]));
        self::assertCount(0, $this->classDescriptions);
    }
}
