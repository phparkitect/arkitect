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
            /** @var Notification[] */
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

                $violations = $this->countViolations();

                return 0 === $violations;
            }

            public function toString(): string
            {
                return 'satifies all constraints';
            }

            protected function failureDescription($other): string
            {
                $violations = $this->countViolations();
                if (0 === $violations) {
                    throw new \LogicException('Attempt to get description without any failure.');
                }

                $firstNote = $this->getFirstViolations();
                $errors = $firstNote->errors();
                if (!isset($errors[0])) {
                    throw new \LogicException('Notification does not have errors');
                }

                return $errors[0];
            }

            protected function countViolations(): int
            {
                return array_reduce(
                    $this->notifications,
                    function (int $count, Notification $notification) {
                        return $count + $notification->getErrorCount();
                    },
                    0
                );
            }

            private function getFirstViolations(): Notification
            {
                foreach ($this->notifications as $notification) {
                    if ($notification->hasErrors()) {
                        return $notification;
                    }
                }

                throw new \LogicException('No violations found');
            }
        };

        static::assertThat($set, $constraint);
    }
}
