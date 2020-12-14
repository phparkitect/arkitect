<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FilePath;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSet;
use Arkitect\Rules\DSL\ArchRule;
use Symfony\Component\Finder\SplFileInfo;

class RuleChecker
{
    private Violations $violations;

    private ClassSet $classSet;

    private FilePath $currentlyAnalyzedFile;

    private Parser $parser;

    /**
     * @var ArchRule[]
     */
    private array $rules;

    public function __construct(
        ClassSet $classSet,
        Parser $parser,
        FilePath $currentlyAnalyzedFile,
        Violations $violations,
        ArchRule ...$rules
    ) {
        $this->classSet = $classSet;
        $this->rules = $rules;
        $this->parser = $parser;
        $this->violations = $violations;
        $this->currentlyAnalyzedFile = $currentlyAnalyzedFile;
    }

    public static function build(ClassSet $classSet, ArchRule ...$rules): self
    {
        $violations = new Violations();
        $currentlyAnalyzedFile = new FilePath();

        $fileParser = new FileParser();

        $fileParser->onClassAnalyzed(static function (ClassDescription $classDescription) use ($currentlyAnalyzedFile, $rules, $violations): void {
            $classDescription->setFullPath($currentlyAnalyzedFile->toString());

            /** @var ArchRule $rule */
            foreach ($rules as $rule) {
                $rule->check($classDescription, $violations);
            }
        });

        return new self($classSet, $fileParser, $currentlyAnalyzedFile, $violations, ...$rules);
    }

    public function run(): Violations
    {
        /** @var SplFileInfo $file */
        foreach ($this->classSet as $file) {
            $this->currentlyAnalyzedFile->set($file->getRelativePath());

            $this->parser->parse($file->getContents());
        }

        return $this->violations;
    }
}
