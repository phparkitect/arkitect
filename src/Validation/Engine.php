<?php
declare(strict_types=1);

namespace Arkitect\Validation;

class Engine
{
    /** @var Rule[]  */
    private $rules = [];

    public function addRule(Rule $rule): void
    {
        $this->rules[] = $rule;
    }

    public function addRules(array $rules): void
    {
        $this->rules[] = array_merge($this->rules, $rules);
    }

    public function run(Item $item): Notification
    {
        $violations = new Notification();

        foreach ($this->rules as $rule) {
            $rule->check($violations, $item);
        }

        return $violations;
    }
}
