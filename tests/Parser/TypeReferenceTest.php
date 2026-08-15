<?php

declare(strict_types=1);

namespace Arkitect\Tests\Parser;

use Arkitect\Parser\TypeReference;
use PHPUnit\Framework\TestCase;

final class TypeReferenceTest extends TestCase
{
    public function test_a_fully_qualified_name_is_accepted(): void
    {
        $reference = new TypeReference('App\Domain\Order', 12);

        self::assertSame('App\Domain\Order', $reference->name);
        self::assertSame(12, $reference->line);
    }

    public function test_a_name_in_the_global_namespace_is_accepted(): void
    {
        self::assertSame('DateTimeImmutable', (new TypeReference('DateTimeImmutable', 1))->name);
    }

    public function test_a_name_with_high_byte_characters_is_accepted(): void
    {
        self::assertSame('App\Caffè', (new TypeReference('App\Caffè', 1))->name);
    }

    /**
     * ClassGraph indexes on this string, so the two spellings would become
     * two unrelated types — the kind of bug that shows up as a rule quietly
     * matching nothing.
     */
    public function test_a_leading_separator_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TypeReference('\App\Domain\Order', 12);
    }

    public function test_an_empty_name_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TypeReference('', 12);
    }

    public function test_a_name_that_is_not_a_type_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TypeReference('not a class name', 12);
    }

    public function test_a_doubled_separator_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TypeReference('App\\\\Order', 12);
    }

    /**
     * php-parser answers -1 when a node has no position, and that would
     * otherwise travel all the way into a violation reported at
     * `src/Foo.php:-1`.
     */
    public function test_the_line_php_parser_uses_for_no_position_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TypeReference('App\Order', -1);
    }

    public function test_line_zero_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TypeReference('App\Order', 0);
    }

    public function test_the_rejection_names_the_offending_value(): void
    {
        $this->expectExceptionMessage('App\Order');

        new TypeReference('App\Order', 0);
    }
}
