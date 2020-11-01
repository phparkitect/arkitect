<?php
declare(strict_types=1);

namespace Arkitect\Validation;

use Arkitect\Analyzer\ClassDescription;

class Engine
{
    /** @var Rule[] */
    private $rules = [];

    public function addRule(Rule $rule): void
    {
        $this->rules[] = $rule;
    }

    public function addRules(array $rules): void
    {
        $this->rules = array_merge($this->rules, $rules);
    }

    public function run(ClassDescription $item): Notification
    {
        $notification = new Notification();

        foreach ($this->rules as $rule) {
            if ($rule->appliesTo($item)) {
                $rule->check($notification, $item);
            }
        }

        return $notification;
    }
}
