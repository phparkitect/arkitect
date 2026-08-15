<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Constraint;

use Arkitect\Evaluate\Constraint\PhpCoreClasses;
use PHPUnit\Framework\TestCase;

final class PhpCoreClassesTest extends TestCase
{
    public function test_an_internal_class_is_recognised(): void
    {
        self::assertTrue((new PhpCoreClasses())->contains('DateTimeImmutable'));
    }

    public function test_an_internal_interface_is_recognised(): void
    {
        self::assertTrue((new PhpCoreClasses())->contains('Countable'));
    }

    public function test_a_userland_class_that_happens_to_be_loaded_is_not_core(): void
    {
        self::assertFalse((new PhpCoreClasses())->contains(self::class));
    }

    /**
     * The answer for an unloadable name has to be "not core" rather than an
     * error: most dependencies of a parsed project are never loaded into
     * the process running arkitect.
     */
    public function test_a_name_that_does_not_exist_in_this_process_is_not_core(): void
    {
        self::assertFalse((new PhpCoreClasses())->contains('App\Domain\NeverLoaded'));
    }

    public function test_the_answer_is_stable_when_asked_twice(): void
    {
        $core = new PhpCoreClasses();

        self::assertTrue($core->contains('DateTimeImmutable'));
        self::assertTrue($core->contains('DateTimeImmutable'));
    }
}
