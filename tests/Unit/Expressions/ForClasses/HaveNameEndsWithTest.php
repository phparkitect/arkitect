<?php

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\HaveNameEndsWith;

class HaveNameEndsWithTest extends TestCase
{
	public function test_check_class_name_starts_with(): void
	{
		$expression = new HaveNameEndsWith('Suffix');

		$goodClass = ClassDescription::build('\App\ClassNameSuffix')->get();

		$violations = new Violations();
		$expression->evaluate($goodClass, $violations);
		self::assertEquals(0, $violations->count());
	}

	public function test_show_violation_when_class_name_does_not_end_with(): void
	{
		$expression = new HaveNameEndsWith('Suffix');

		$badClass = ClassDescription::build('\App\BadNameClass')->get();

		$violations = new Violations();
		$expression->evaluate($badClass, $violations);
		self::assertNotEquals(0, $violations->count());
		$this->assertEquals('should have a name that ends with Suffix', $expression->describe($badClass)->toString());
	}
}
