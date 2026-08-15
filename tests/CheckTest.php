<?php

declare(strict_types=1);

namespace Arkitect\Tests;

use Arkitect\Check;
use Arkitect\Evaluate\Constraint;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\Rules;
use Arkitect\Evaluate\Selector;
use Arkitect\Parser\TargetPhpVersion;
use Arkitect\ProjectParser;
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

    /** @param array<string, string> $files */
    private function check(array $files, Rule $rule): \Arkitect\CheckResult
    {
        $repository = new InMemoryFileRepository();

        foreach ($files as $path => $contents) {
            $repository = $repository->withFile($path, $contents);
        }

        return (new Check(new ProjectParser($repository)))
            ->run(new Rules($rule), TargetPhpVersion::create('8.5'));
    }
}
