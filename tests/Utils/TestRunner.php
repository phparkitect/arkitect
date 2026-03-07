<?php

declare(strict_types=1);

namespace Arkitect\Tests\Utils;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassHierarchyResolver;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\ClassSet;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;

class TestRunner
{
    private Violations $violations;

    private ParsingErrors $parsingErrors;

    private ?string $version;

    private ?ClassHierarchyResolver $resolver;

    private function __construct(?string $version = null, ?ClassHierarchyResolver $resolver = null)
    {
        $this->version = $version;
        $this->resolver = $resolver;
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();
    }

    public static function create(?string $version = null, ?ClassHierarchyResolver $resolver = null): self
    {
        return new self($version, $resolver);
    }

    public function run(string $srcPath, ArchRule ...$rules): void
    {
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();

        $hierarchyResolver = $this->resolver ?? new ClassHierarchyResolver([$srcPath]);
        $fileParser = FileParserFactory::createFileParser(
            TargetPhpVersion::create($this->version),
            true,
            $hierarchyResolver
        );

        $classSet = ClassSet::fromDir($srcPath);

        foreach ($classSet as $file) {
            $fileParser->parse($file->getContents(), $file->getRelativePathname());

            $parsedErrors = $fileParser->getParsingErrors();

            foreach ($parsedErrors as $parsedError) {
                $this->parsingErrors->add($parsedError);
            }

            /** @var ClassDescription $classDescription */
            foreach ($fileParser->getClassDescriptions() as $classDescription) {
                foreach ($rules as $rule) {
                    $rule->check($classDescription, $this->violations);
                }
            }
        }
    }

    public function getViolations(): Violations
    {
        return $this->violations;
    }

    public function getParsingErrors(): ParsingErrors
    {
        return $this->parsingErrors;
    }
}
