<?php

declare(strict_types=1);

namespace Arkitect\Tests;

use Arkitect\Baseline;
use Arkitect\Command\Check;
use Arkitect\Command\CheckResult;
use Arkitect\Config;
use Arkitect\Evaluate\Constraint;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\Selector;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\RepositoryParser;
use Arkitect\Tests\FileSystem\InMemoryBaselineRepository;
use Arkitect\Tests\FileSystem\InMemoryFileRepository;
use PHPUnit\Framework\TestCase;

/**
 * The first test that runs every stage together. It needs no disk because
 * file access is a port, which is what that port was for.
 */
final class CheckTest extends TestCase
{
    public function test_a_codebase_that_satisfies_its_rules_comes_back_clean(): void
    {
        $result = $this->check(
            ['src/Order.php' => "<?php\nnamespace App\\Domain;\nfinal class Order {}\n"],
            Rule::allClasses()
                ->that(new Selector\ResideInNamespace('App\Domain'))
                ->should(new Constraint\IsFinal())
                ->because('domain objects are not meant to be extended')
        );

        self::assertTrue($result->isClean());
        self::assertSame(1, $result->classesChecked);
    }

    public function test_a_violation_travels_from_source_to_result(): void
    {
        $result = $this->check(
            ['src/Order.php' => "<?php\nnamespace App\\Domain;\nclass Order {}\n"],
            Rule::allClasses()->should(new Constraint\IsFinal())->because('reasons')
        );

        self::assertFalse($result->isClean());

        $violation = iterator_to_array(iterator_to_array($result->ruleResults)[0]->violations)[0];
        self::assertSame('App\Domain\Order', $violation->fqcn);
        self::assertSame('src/Order.php', $violation->filePath);
        self::assertSame(3, $violation->line);
    }

    /**
     * The reason vendor/ is parsed at all: the project class only resolves
     * because its parent was read, and the parent is not itself judged.
     */
    public function test_dependencies_resolve_without_being_checked(): void
    {
        $result = $this->check([
            'src/Handler.php' => "<?php\nnamespace App;\nfinal class Handler extends \\Acme\\Base {}\n",
            'vendor/acme/Base.php' => "<?php\nnamespace Acme;\nclass Base implements Contract {}\n",
            'vendor/acme/Contract.php' => "<?php\nnamespace Acme;\ninterface Contract {}\n",
        ], Rule::allClasses()->should(new Constraint\IsA('Acme\Contract'))->because('reasons'));

        self::assertTrue($result->isClean());
        self::assertSame(1, $result->classesChecked);
    }

    public function test_a_file_that_cannot_be_parsed_fails_the_run(): void
    {
        $result = $this->check(
            ['src/Broken.php' => '<?php class {{{ broken'],
            Rule::allClasses()->should(new Constraint\IsFinal())->because('reasons')
        );

        self::assertFalse($result->isClean());
        self::assertSame(
            ['src/Broken.php'],
            array_unique(array_map(static fn ($e) => $e->filePath, iterator_to_array($result->parsingErrors)))
        );
    }

    /**
     * What a baseline is for: the violation that was there when the project
     * adopted arkitect stays quiet, and one introduced afterwards does not.
     */
    public function test_a_baselined_violation_is_silenced_and_a_new_one_is_not(): void
    {
        $files = [
            'src/Old.php' => "<?php\nnamespace App;\nclass Old {}\n",
            'src/New.php' => "<?php\nnamespace App;\nclass NewOne {}\n",
        ];
        $rule = Rule::allClasses()->should(new Constraint\IsFinal())->because('everything is final');

        $everything = $this->check($files, $rule);
        self::assertCount(2, iterator_to_array($everything->ruleResults)[0]->violations);

        $known = Baseline::of(new Violations(...array_filter(
            iterator_to_array(iterator_to_array($everything->ruleResults)[0]->violations),
            static fn ($violation) => 'App\Old' === $violation->fqcn
        )));

        $result = $this->check($files, $rule, $known);

        self::assertSame(1, $result->baselined);
        self::assertFalse($result->isClean());

        $left = iterator_to_array(iterator_to_array($result->ruleResults)[0]->violations);
        self::assertCount(1, $left);
        self::assertSame('App\NewOne', $left[0]->fqcn);
    }

    public function test_a_run_with_everything_baselined_is_clean(): void
    {
        $files = ['src/Old.php' => "<?php\nnamespace App;\nclass Old {}\n"];
        $rule = Rule::allClasses()->should(new Constraint\IsFinal())->because('everything is final');

        $known = Baseline::of(iterator_to_array($this->check($files, $rule)->ruleResults)[0]->violations);

        $result = $this->check($files, $rule, $known);

        self::assertTrue($result->isClean());
        self::assertSame(1, $result->baselined);
    }

    /** @param array<string, string> $files */
    private function check(array $files, Rule $rule, ?Baseline $baseline = null): CheckResult
    {
        $repository = new InMemoryFileRepository();

        foreach ($files as $path => $contents) {
            $repository = $repository->withFile($path, $contents);
        }

        $config = Config::create(__DIR__)->add([$rule])->targetPhpVersion('8.5');
        $storage = new InMemoryBaselineRepository();

        if (null !== $baseline) {
            $config = $config->baseline('known.json');
            $storage = new InMemoryBaselineRepository(['known.json' => $baseline]);
        }

        return (new Check(new RepositoryParser($repository), $storage))->run($config);
    }
}
