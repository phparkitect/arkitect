<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\HaveNameStartingWith;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class HaveNameStartingWithTest extends TestCase
{
    public function test_check_class_name_starts_with_given_string(): void
    {
        $expression = new HaveNameStartingWith('Prefix');

        $goodClass = ClassDescription::build('\App\PrefixClassName')->get();

        $violations = new Violations();
        $expression->evaluate($goodClass, $violations);
        self::assertEquals(0, $violations->count());
    }

    public function test_show_violation_when_class_name_does_not_start_with_given_string(): void
    {
        $expression = new HaveNameStartingWith('Prefix');

        $badClass = ClassDescription::build('\App\BadNameClass')->get();

        $violations = new Violations();
        $expression->evaluate($badClass, $violations);
        self::assertNotEquals(0, $violations->count());
        self::assertEquals('should have a name that starts with Prefix', $expression->describe($badClass)->toString());
    }
}
