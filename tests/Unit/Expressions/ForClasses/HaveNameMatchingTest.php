<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class HaveNameMatchingTest extends TestCase
{
    public function test_check_class_name_match(): void
    {
        $expression = new HaveNameMatching('*Class');

        $goodClass = ClassDescription::build('\App\MyClass')->get();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($goodClass, $violations, $because, false);
        self::assertEquals(0, $violations->count());
    }

    public function test_show_violation_when_class_name_does_not_match(): void
    {
        $expression = new HaveNameMatching('*GoodName*');

        $badClass = ClassDescription::build('\App\BadNameClass')->get();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $expression->evaluate($badClass, $violations, $because, false);
        self::assertNotEquals(0, $violations->count());
        $this->assertEquals(
            'should have a name that matches *GoodName* because we want to add this rule for our software',
            $expression->describe($badClass, $because, false)->toString()
        );
    }
}
