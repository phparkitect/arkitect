<?php

declare(strict_types=1);

namespace Arkitect\Tests;

use Arkitect\Config;
use Arkitect\ConfigDraft;
use Arkitect\Evaluate\Constraint;
use Arkitect\Evaluate\Rule;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function test_a_config_knows_its_root_and_its_rules(): void
    {
        $config = Config::create()->root(__DIR__)->add([$this->aRule()]);

        self::assertSame(__DIR__, $config->root);
        self::assertCount(1, $config->rules);
    }

    /**
     * Not inferred from the working directory or from where the config file
     * happens to sit: a run must mean the same thing wherever it is started
     * from. The draft is what makes it unskippable — nothing can be added to
     * a config that has no root, because that object has no add().
     */
    public function test_nothing_can_be_added_before_a_root_is_given(): void
    {
        $offered = array_values(array_diff(get_class_methods(ConfigDraft::class), ['__construct']));

        self::assertSame(['root'], $offered);
    }

    public function test_a_root_that_is_not_a_directory_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('/does/not/exist');

        Config::create()->root('/does/not/exist');
    }

    public function test_rules_accumulate_across_calls(): void
    {
        $config = Config::create()->root(__DIR__)
            ->add([$this->aRule()])
            ->add([$this->aRule(), $this->aRule()]);

        self::assertCount(3, $config->rules);
    }

    public function test_only_rules_can_be_added(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Config::create()->root(__DIR__)->add(['not a rule']);
    }

    private function aRule(): Rule
    {
        return Rule::allClasses()->should(new Constraint\IsFinal())->because('reasons');
    }
}
