<?php
declare(strict_types=1);

namespace ArkitectTests\unit;

use Arkitect\Validation\Engine;
use Arkitect\Validation\Item;
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

        $item = new class implements Item {
            public function toString(): string
            {
                return 'App\Kernel';
            }
        };

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
            ->check(Argument::type(Notification::class), Argument::type(Item::class))
            ->shouldBeCalled();

        $rule2 = $this->prophesize(Rule::class);
        $rule2
            ->check(Argument::type(Notification::class), Argument::type(Item::class))
            ->shouldBeCalled();

        return [$rule1->reveal(), $rule2->reveal()];
    }
}
