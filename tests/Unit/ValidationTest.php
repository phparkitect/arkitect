<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Validation\Engine;
use Arkitect\Validation\Notification;
use Arkitect\Validation\Rule;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ValidationTest extends TestCase
{
    public function test_validation(): void
    {
        $engine = new Engine();

        foreach ($this->rules() as $rule) {
            $engine->addRule($rule);
        }

        $item = new ClassDescription(
            __FILE__,
            FullyQualifiedClassName::fromString(self::class),
            [],
            []
        );

        $notification = $engine->run($item);

        self::assertInstanceOf(Notification::class, $notification);
    }

    /**
     * @return Rule[]
     */
    private function rules(): array
    {
        $rule1 = $this->prophesize(Rule::class);
        $rule1
            ->check(Argument::type(Notification::class), Argument::type(ClassDescription::class))
            ->shouldBeCalled();
        $rule1->appliesTo(Argument::type(ClassDescription::class))->willReturn(true);

        $rule2 = $this->prophesize(Rule::class);
        $rule2
            ->check(Argument::type(Notification::class), Argument::type(ClassDescription::class))
            ->shouldBeCalled();
        $rule2->appliesTo(Argument::type(ClassDescription::class))->willReturn(true);

        return [$rule1->reveal(), $rule2->reveal()];
    }
}
