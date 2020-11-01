<?php
declare(strict_types=1);

namespace Arkitect\PHPUnit;

use Arkitect\Analyzer\Events\ClassAnalyzed;
use Arkitect\ClassSet;
use Arkitect\Validation\Engine;
use Arkitect\Validation\Notification;
use Arkitect\Validation\Rule;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ArchRuleTestCase extends TestCase
{
    public static function assertArchRule(Rule $rule, ClassSet $set): void
    {
        $constraint = new class($rule) extends Constraint implements EventSubscriberInterface {
            private $engine;
            private $notifications = [];

            public function __construct(Rule $rule)
            {
                $this->engine = new Engine();
                $this->engine->addRule($rule);
            }

            public static function getSubscribedEvents()
            {
                return [
                    ClassAnalyzed::class => 'onClassAnalyzed',
                ];
            }

            public function onClassAnalyzed(ClassAnalyzed $classAnalyzed): void
            {
                $item = $classAnalyzed->getClassDescription();

                $this->notifications[] = $this->engine->run($item);
            }

            protected function matches($set): bool
            {
                $set->addSubscriber($this);
                $set->run();

                $violations = array_reduce(
                    $this->notifications,
                    function (int $count, Notification $notification) {
                        return $count + $notification->getErrorCount();
                    },
                    0
                );

                return 0 === $violations;
            }

            public function toString(): string
            {
                return 'satifies all constraints';
            }

            protected function failureDescription($other): string
            {
                if (0 === \count($this->notifications)) {
                    throw new \LogicException('Attempt to get description without any failure.');
                }

                /** @var Notification $firstNote */
                $firstNote = $this->notifications[0];

                $errors = $firstNote->errors();
                if (!isset($errors[0])) {
                    throw new \LogicException('Attempt to get description without any failure.');
                }

                return $errors[0];
            }
        };

        static::assertThat($set, $constraint);
    }
}
