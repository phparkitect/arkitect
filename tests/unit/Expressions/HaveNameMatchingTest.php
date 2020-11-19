<?php
declare(strict_types=1);

namespace ArkitectTests\unit\Expressions;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\HaveNameMatching;
use PHPUnit\Framework\TestCase;

class HaveNameMatchingTest extends TestCase
{
    public function test_check_class_name_match(): void
    {
        $expression = new HaveNameMatching('**Class');

        $goodClass = ClassDescription::build('\App\MyClass', 'App')->get();
        $this->assertFalse($expression->evaluate($goodClass));
    }

    public function test_show_violation_when_class_name_does_not_match(): void
    {
        $expression = new HaveNameMatching('**GoodName**');

        $badClass = ClassDescription::build('\App\BadNameClass', 'App')->get();
        $this->assertTrue($expression->evaluate($badClass));
        $this->assertEquals('\App\BadNameClass has a name that doesn\'t match **GoodName**', $expression->describe($badClass));
    }
}
