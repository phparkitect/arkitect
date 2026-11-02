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

        self::assertSame(__DIR__, $config->root());
        self::assertCount(1, $config->rules());
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

        self::assertCount(3, $config->rules());
    }

    public function test_only_rules_can_be_added(): void
    {
        $this->expectException(\TypeError::class);

        Config::create(__DIR__)->add(['not a rule']); // @phpstan-ignore-line
    }

    /**
     * Optional settings are fluent and mutate; only the root is required, so
     * only the root is a constructor argument. The default is the
     * interpreter running arkitect, which is a different thing from the
     * version the analysed project targets — #650 is about pinning the
     * second.
     */
    public function test_the_target_php_version_defaults_to_the_running_one(): void
    {
        $running = \PHP_MAJOR_VERSION.'.'.\PHP_MINOR_VERSION;

        self::assertSame($running, Config::create(__DIR__)->targetPhpVersion()->value);
    }

    public function test_the_target_php_version_can_be_pinned(): void
    {
        self::assertSame('8.1', Config::create(__DIR__)->withTargetPhpVersion('8.1')->targetPhpVersion()->value);
    }

    public function test_pinning_it_keeps_the_rules_and_the_root(): void
    {
        $config = Config::create(__DIR__)->add([$this->aRule()])->withTargetPhpVersion('8.1');

        self::assertSame(__DIR__, $config->root());
        self::assertCount(1, $config->rules());
    }

    public function test_adding_rules_keeps_the_pinned_version(): void
    {
        $config = Config::create(__DIR__)->withTargetPhpVersion('8.1')->add([$this->aRule()]);

        self::assertSame('8.1', $config->targetPhpVersion()->value);
    }

    public function test_the_dependency_directories_default_to_vendor(): void
    {
        $config = Config::create(__DIR__);

        self::assertTrue($config->dependencies()->contains('vendor/acme/Base.php'));
        self::assertFalse($config->dependencies()->contains('src/Order.php'));
    }

    /**
     * Declaring replaces the default rather than adding to it, so a config
     * that names only its monorepo packages stops excluding the root vendor.
     */
    public function test_declaring_dependency_directories_replaces_the_default(): void
    {
        $config = Config::create(__DIR__)->withDependencies('packages/*/vendor');

        self::assertTrue($config->dependencies()->contains('packages/billing/vendor/acme/Dep.php'));
        self::assertFalse($config->dependencies()->contains('vendor/acme/Base.php'));
    }

    /**
     * The reason the setters are named `with`: a config file that calls one
     * and drops the result has lost the declaration, and a name reading as
     * a command would have hidden that.
     */
    public function test_a_dropped_result_changes_nothing(): void
    {
        $config = Config::create(__DIR__);

        $config->withBaseline('ba.json');
        $config->withTargetPhpVersion('8.1');
        $config->withDependencies('build');
        $config->add([$this->aRule()]);

        self::assertNull($config->baseline());
        self::assertCount(0, $config->rules());
        self::assertTrue($config->dependencies()->contains('vendor/acme/Base.php'));
    }

    public function test_nothing_it_holds_is_publicly_assignable(): void
    {
        foreach ((new \ReflectionClass(Config::class))->getProperties() as $property) {
            self::assertFalse($property->isPublic(), $property->getName().' is public');
            self::assertTrue($property->isReadOnly(), $property->getName().' is not readonly');
        }
    }

    private function aRule(): Rule
    {
        return Rule::allClasses()->should(new Constraint\IsFinal())->because('reasons');
    }
}
