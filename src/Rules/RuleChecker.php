<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FilePath;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSet;
use Symfony\Component\Finder\SplFileInfo;

class RuleChecker
{
    private Violations $violations;

    private \Arkitect\Rules\DSL\ArchRule $rule;

    private ClassSet $classSet;

    private FilePath $currentlyAnalyzedFile;

    private Parser $parser;

    public function __construct(ClassSet $classSet, DSL\ArchRule $rule, Parser $parser, FilePath $currentlyAnalyzedFile, Violations $violations)
    {
        $this->classSet = $classSet;
        $this->rule = $rule;
        $this->parser = $parser;
        $this->violations = $violations;
        $this->currentlyAnalyzedFile = $currentlyAnalyzedFile;
    }

    public static function build(ClassSet $classSet, DSL\ArchRule $rule): self
    {
        $violations = new Violations();
        $currentlyAnalyzedFile = new FilePath();

        $fileParser = new FileParser();
        $fileParser->onClassAnalyzed(static function (ClassDescription $classDescription) use ($currentlyAnalyzedFile, $rule, $violations): void {
            $classDescription->setFullPath($currentlyAnalyzedFile->toString());

            $rule->check($classDescription, $violations);
        });

        return new self($classSet, $rule, $fileParser, $currentlyAnalyzedFile, $violations);
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
