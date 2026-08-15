<?php

declare(strict_types=1);

namespace Arkitect\Tests\Parser;

use Arkitect\Parser\Fqcn;
use PHPUnit\Framework\TestCase;

final class FqcnTest extends TestCase
{
    public function test_it_keeps_a_name_that_is_already_in_the_one_spelling(): void
    {
        self::assertSame('App\Domain\Order', (new Fqcn('App\Domain\Order'))->toString());
    }

    /**
     * The spelling people write when copying from source code, and the whole
     * reason this class exists.
     */
    public function test_a_leading_separator_is_normalized_away(): void
    {
        self::assertSame('App\Domain\Order', (new Fqcn('\App\Domain\Order'))->toString());
    }

    public function test_a_global_class_is_a_valid_name(): void
    {
        self::assertSame('DateTimeImmutable', (new Fqcn('\DateTimeImmutable'))->toString());
    }

    public function test_a_doubled_leading_separator_is_not_a_name_anyone_meant(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Fqcn('\\\\App\Order');
    }

    public function test_a_trailing_separator_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Fqcn('App\Order\\');
    }

    public function test_something_that_is_not_a_name_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Fqcn('App\Order; DROP TABLE');
    }

    public function test_an_empty_name_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Fqcn('');
    }

    public function test_the_rejection_shows_what_was_given_not_what_was_normalized(): void
    {
        $this->expectExceptionMessage("'\\\\App\Order' is not a fully qualified class name.");

        new Fqcn('\\\\App\Order');
    }

    public function test_it_splits_a_name_into_its_parts(): void
    {
        $fqcn = new Fqcn('App\Domain\Order');

        self::assertSame('Order', $fqcn->shortName());
        self::assertSame('App\Domain', $fqcn->namespaceName());
    }

    public function test_a_global_class_has_no_namespace(): void
    {
        $fqcn = new Fqcn('Order');

        self::assertSame('Order', $fqcn->shortName());
        self::assertSame('', $fqcn->namespaceName());
    }
}
