<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\NotParsedClasses;
use Arkitect\Rules\ParsingError;
use Arkitect\Rules\ParsingErrors;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

class FileParser implements Parser
{
    /** @var \PhpParser\Parser */
    private $parser;

    /** @var \PhpParser\NodeTraverser */
    private $traverser;

    /** @var FileVisitor */
    private $fileVisitor;

    /** @var ClassDescriptionCollection */
    private $classDescriptionsParsed;
    /**
     * @var array
     */
    private $classDescriptions;
    /**
     * @var FileContentGetterInterface
     */
    private $fileContentGetter;
    /**
     * @var array
     */
    private $skippedClasses;

    /** @var ParsingErrors */
    private $parsingErrors;

    public function __construct(
        NodeTraverser $traverser,
        FileVisitor $fileVisitor,
        NameResolver $nameResolver,
        TargetPhpVersion $targetPhpVersion,
        FileContentGetterInterface $fileContentGetter
    ) {
        $this->fileVisitor = $fileVisitor;
        $this->fileContentGetter = $fileContentGetter;

        $lexer = new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'],
            'phpVersion' => $targetPhpVersion->get() ?? phpversion(),
        ]);

        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);
        $this->traverser = $traverser;
        $this->traverser->addVisitor($nameResolver);
        $this->traverser->addVisitor($this->fileVisitor);
        $this->classDescriptionsParsed = new ClassDescriptionCollection();
        $this->classDescriptions = [];
        $this->skippedClasses = [];
    }

    /**
     * @return ClassDescription[]
     */
    public function getClassDescriptions(): array
    {
        return $this->classDescriptions;
    }

    public function parse(string $fileContent, string $filename, array $classDescriptionToParse, ParsingErrors $parsingErrors): array
    {
        $this->parsingErrors = $parsingErrors;
        try {
            $this->fileVisitor->clearParsedClassDescriptions();

            $classDescriptionsToParse = $this->generateClassDescriptions($filename, $fileContent);

            /** @var ClassDescription $classDescription */
            foreach ($classDescriptionsToParse as $classDescription) {
                $classDescriptionToParse[] = $classDescription;
                if ($this->classDescriptionsParsed->exists($classDescription->getFQCN())) {
                    continue;
                }

                $this->classDescriptionsParsed->add($classDescription);
                $this->parseDependencies($classDescription);
            }
        } catch (\Throwable $e) {
            echo 'Parse Error: ', $e->getMessage();
            print_r($e->getTraceAsString());
        }

        return $classDescriptionToParse;
    }

    public function getParsingErrors(): ParsingErrors
    {
        return $this->parsingErrors;
    }

    public function getFileVisitor(): FileVisitor
    {
        return $this->fileVisitor;
    }

    public function getClassDescriptionsParsed(): ClassDescriptionCollection
    {
        return $this->classDescriptionsParsed;
    }

    public function getNotParsedClasses(): NotParsedClasses
    {
        return $this->fileContentGetter->getNotParsedClasses();
    }

    private function parseDependencies(ClassDescription $classDescription): void
    {
        $classDependencies = $classDescription->getDependencies();

        $missingDeps = array_diff_key(
            $classDependencies,
            $this->classDescriptionsParsed->getClassDescriptions()
        );

        $this->searchDependencies($missingDeps);
    }

    private function searchDependencies(array $classDependencies): void
    {
        /** @var ClassDependency $dependency */
        foreach ($classDependencies as $dependency) {
            $this->fileVisitor->clearParsedClassDescriptions();

            if ($this->classDescriptionsParsed->exists($dependency->getFQCN()->toString()) ||
                \in_array($dependency->getFQCN()->toString(), $this->skippedClasses)
            ) {
                continue;
            }

            $this->fileContentGetter->open($dependency->getFQCN()->toString());

            if (!$this->fileContentGetter->isContentAvailable()) {
                $cd = ClassDescription::build($dependency->getFQCN()->toString());
                $this->classDescriptionsParsed->add($cd->get());

                $this->skippedClasses[] = $dependency->getFQCN()->toString();
                $errorRetrieved = $this->fileContentGetter->getError();
                if (null === $errorRetrieved) {
                    continue;
                }

                if (!$this->isAlreadyInErrors($errorRetrieved->getError())) {
                    $this->parsingErrors->add($errorRetrieved);
                }
                continue;
            }

            $content = $this->fileContentGetter->getContent();
            $filename = $this->fileContentGetter->getFileName();

            if (null === $content || null === $filename) {
                $cd = ClassDescription::build($dependency->getFQCN()->toString());
                $this->classDescriptionsParsed->add($cd->get());

                $this->skippedClasses[] = $dependency->getFQCN()->toString();
                $errorRetrieved = $this->fileContentGetter->getError();
                if (null === $errorRetrieved) {
                    continue;
                }

                if (!$this->isAlreadyInErrors($errorRetrieved->getError())) {
                    $this->parsingErrors->add($errorRetrieved);
                }

                continue;
            }

            $classDescriptionsFound = $this->generateClassDescriptions($filename, $content);

            /** @var ClassDescription $classDescriptionFound */
            foreach ($classDescriptionsFound as $classDescriptionFound) {
                if ($this->classDescriptionsParsed->exists($classDescriptionFound->getFQCN())) {
                    continue;
                }

                $this->classDescriptionsParsed->add($classDescriptionFound);
                $this->parseDependencies($classDescriptionFound);
            }
        }
    }

    private function generateClassDescriptions(string $filename, string $fileContent): array
    {
        $errorHandler = new Collecting();
        $stmts = $this->parser->parse($fileContent, $errorHandler);

        if ($errorHandler->hasErrors()) {
            foreach ($errorHandler->getErrors() as $error) {
                if (!$this->isAlreadyInErrors($error->getMessage())) {
                    $this->parsingErrors->add(ParsingError::create($filename, $error->getMessage()));
                }
            }
        }

        if (null === $stmts) {
            return [];
        }

        $this->traverser->traverse($stmts);

        return $this->fileVisitor->getClassDescriptions();
    }

    private function isAlreadyInErrors(string $error): bool
    {
        /** @var ParsingError $errorParsed */
        foreach ($this->parsingErrors->toArray() as $errorParsed) {
            if ($errorParsed->getError() === $error) {
                return true;
            }
        }

        return false;
    }
}
