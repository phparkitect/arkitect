<?php

declare(strict_types=1);

namespace Arkitect\Tests\Resolve;

use Arkitect\Resolve\InternalClasses;
use PHPUnit\Framework\TestCase;

final class InternalClassesTest extends TestCase
{
    public function test_an_internal_class_is_recognised(): void
    {
        self::assertTrue((new InternalClasses())->contains('DateTimeImmutable'));
    }

    public function test_an_internal_interface_is_recognised(): void
    {
        self::assertTrue((new InternalClasses())->contains('Countable'));
    }

    /**
     * The reason this isn't called PhpCoreClasses: an extension's classes
     * are internal too, and have no more source to parse than SPL does.
     */
    public function test_a_class_from_an_extension_is_internal_as_well(): void
    {
        self::assertTrue((new InternalClasses())->contains('ReflectionClass'));
    }

    public function test_a_user_defined_class_that_happens_to_be_loaded_is_not_internal(): void
    {
        self::assertFalse((new InternalClasses())->contains(self::class));
    }

    /**
     * The answer for an unloadable name has to be "not internal" rather
     * than an error: most dependencies of a parsed project are never loaded
     * into the process running arkitect.
     */
    public function test_a_name_that_does_not_exist_in_this_process_is_not_internal(): void
    {
        self::assertFalse((new InternalClasses())->contains('App\Domain\NeverLoaded'));
    }

    public function test_the_answer_is_stable_when_asked_twice(): void
    {
        $internal = new InternalClasses();

        self::assertTrue($internal->contains('DateTimeImmutable'));
        self::assertTrue($internal->contains('DateTimeImmutable'));
    }
}
