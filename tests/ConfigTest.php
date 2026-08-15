<?php

declare(strict_types=1);

namespace Arkitect\Tests;

use Arkitect\Config;
use Arkitect\Evaluate\Constraint;
use Arkitect\Evaluate\Rule;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function test_a_config_knows_its_root_and_its_rules(): void
    {
        $config = Config::create(__DIR__)->add([$this->aRule()]);

        self::assertSame(__DIR__, $config->root);
        self::assertCount(1, $config->rules);
    }

    /**
     * Not inferred from the working directory or from where the config file
     * happens to sit: a run must mean the same thing wherever it is started
     * from. PHP already refuses to build an object without a required
     * argument, so the root is one — no builder needed to make it unskippable.
     */
    public function test_a_config_cannot_be_built_without_a_root(): void
    {
        $root = (new \ReflectionClass(Config::class))->getConstructor()?->getParameters()[0];

        self::assertSame('root', $root?->getName());
        self::assertFalse($root?->isOptional());
    }

    public function test_a_root_that_is_not_a_directory_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('/does/not/exist');

        Config::create('/does/not/exist');
    }

    public function test_rules_accumulate_across_calls(): void
    {
        $config = Config::create(__DIR__)
            ->add([$this->aRule()])
            ->add([$this->aRule(), $this->aRule()]);

        self::assertCount(3, $config->rules);
    }

    public function test_only_rules_can_be_added(): void
    {
        $this->expectException(\TypeError::class);

        Config::create(__DIR__)->add(['not a rule']); // @phpstan-ignore-line
    }

    /**
     * Optional, so it is fluent — and it defaults to the interpreter running
     * arkitect, which is a different thing from the version the analysed
     * project targets. #650 is about being able to pin the second.
     */
    public function test_the_target_php_version_defaults_to_the_running_one(): void
    {
        $running = \PHP_MAJOR_VERSION.'.'.\PHP_MINOR_VERSION;

        self::assertSame($running, Config::create(__DIR__)->targetPhpVersion->toString());
    }

    public function test_the_target_php_version_can_be_pinned(): void
    {
        self::assertSame('8.1', Config::create(__DIR__)->targetPhpVersion('8.1')->targetPhpVersion->toString());
    }

    public function test_pinning_it_keeps_the_rules_and_the_root(): void
    {
        $config = Config::create(__DIR__)->add([$this->aRule()])->targetPhpVersion('8.1');

        self::assertSame(__DIR__, $config->root);
        self::assertCount(1, $config->rules);
    }

    public function test_adding_rules_keeps_the_pinned_version(): void
    {
        $config = Config::create(__DIR__)->targetPhpVersion('8.1')->add([$this->aRule()]);

        self::assertSame('8.1', $config->targetPhpVersion->toString());
    }

    private function aRule(): Rule
    {
        return Rule::allClasses()->should(new Constraint\IsFinal())->because('reasons');
    }
}
