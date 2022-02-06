<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Rules\ParsingError;

class FileContentGetter implements FileContentGetterInterface
{
    /** @var ?string */
    private $content;
    /**
     * @var ParsingError
     */
    private $parsingError;
    /**
     * @var ?string
     */
    private $fileName;

    public function open(string $classFQCN): void
    {
        $this->content = null;
        $this->fileName = null;
        try {
            if (!class_exists($classFQCN) && !interface_exists($classFQCN)) {
                $this->parsingError = ParsingError::create($classFQCN, 'Class "'.$classFQCN.'" does not exist');

                return;
            }
            $classReflection = new \ReflectionClass($classFQCN);
            $fileName = $classReflection->getFileName();

            if (!$fileName) {
                return;
            }

            $this->fileName = $fileName;
            $content = file_get_contents($fileName);

            if (!$content) {
                $this->parsingError = ParsingError::create($classFQCN, 'Class "'.$classFQCN.'" without content');

                return;
            }

            $this->content = $content;
        } catch (\Exception $e) {
            $this->parsingError = ParsingError::create($classFQCN, $e->getMessage());
        }
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function isContentAvailable(): bool
    {
        return null !== $this->content;
    }

    public function getError(): ?ParsingError
    {
        return $this->parsingError;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }
}
