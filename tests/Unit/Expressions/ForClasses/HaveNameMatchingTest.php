<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionCollection;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class HaveNameMatchingTest extends TestCase
{
    public function test_check_class_name_match(): void
    {
        $expression = new HaveNameMatching('*Class');

        $goodClass = ClassDescription::build('\App\MyClass')->get();

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($goodClass);

        $violations = new Violations();
        $expression->evaluate($goodClass, $violations, $classDescriptionCollection);
        self::assertEquals(0, $violations->count());
    }

    public function test_show_violation_when_class_name_does_not_match(): void
    {
        $expression = new HaveNameMatching('*GoodName*');

        $badClass = ClassDescription::build('\App\BadNameClass')->get();

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($badClass);

        $violations = new Violations();
        $expression->evaluate($badClass, $violations, $classDescriptionCollection);
        self::assertNotEquals(0, $violations->count());
        $this->assertEquals('should have a name that matches *GoodName*', $expression->describe($badClass)->toString());
    }
}
