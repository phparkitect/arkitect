<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\NotHaveNameMatching;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotHaveNameMatchingTest extends TestCase
{
    public function test_check_class_name_match_create_violation_if_name_matches(): void
    {
        $expression = new NotHaveNameMatching('*Class');

        $myClass = ClassDescription::build('\App\MyClass')->get();

        $violations = new Violations();
        $expression->evaluate($myClass, $violations);
        self::assertEquals(1, $violations->count());
        $this->assertEquals('should not have a name that matches *Class', $expression->describe($myClass)->toString());
    }

    public function test_show_violation_when_class_name_does_not_match(): void
    {
        $expression = new NotHaveNameMatching('*GoodName*');

        $badClass = ClassDescription::build('\App\BadNameClass')->get();

        $violations = new Violations();
        $expression->evaluate($badClass, $violations);
        self::assertEquals(0, $violations->count());
    }
}
