<?php
declare(strict_types=1);

namespace Arkitect\Tests\Expression;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\IsNotFinal;
use Arkitect\Expression\NegateDecorator;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NegateDecoratorTest extends TestCase
{
    public function test_positive_decoration(): void
    {
        $finalClass = ClassDescription::build('Tests\FinalClass')
            ->setFinal(true)
            ->get();

        $isFinal = new IsFinal();

        $isFinal->evaluate($finalClass, $violations = new Violations(), 'of some reason');
        self::assertEquals('FinalClass should be final because of some reason', $isFinal->describe($finalClass, 'of some reason')->toString());

        self::assertEquals(0, $violations->count());

        $isNotFinal = new NegateDecorator($isFinal);

        $isNotFinal->evaluate($finalClass, $violations = new Violations(), 'of some reason');
        self::assertEquals('FinalClass should not be final because of some reason', $isNotFinal->describe($finalClass, 'of some reason')->toString());

        self::assertEquals(1, $violations->count());
    }

    public function test_negative_decoration(): void
    {
        $finalClass = ClassDescription::build('Tests\FinalClass')
            ->setFinal(true)
            ->get();

        $isNotFinal = new IsNotFinal();

        $isNotFinal->evaluate($finalClass, $violations = new Violations(), '');

        self::assertEquals(1, $violations->count());
        self::assertEquals('FinalClass should not be final because of some reason', $isNotFinal->describe($finalClass, 'of some reason')->toString());

        $isFinal = new NegateDecorator($isNotFinal);

        $isFinal->evaluate($finalClass, $violations = new Violations(), '');

        self::assertEquals(0, $violations->count());
        self::assertEquals('FinalClass should be final because of some reason', $isFinal->describe($finalClass, 'of some reason')->toString());
    }
}
