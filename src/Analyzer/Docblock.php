<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class Docblock
{
    private PhpDocNode $phpDocNode;

    public function __construct(PhpDocNode $phpDocNode)
    {
        $this->phpDocNode = $phpDocNode;
    }

    public function getParamTagTypesByName(string $name): ?string
    {
        foreach ($this->phpDocNode->getParamTagValues() as $paramTag) {
            if ($paramTag->parameterName === $name) {
                return $this->getType($paramTag->type);
            }
        }

        return null;
    }

    public function getReturnTagTypes(): array
    {
        $returnTypes = array_map(
            fn (ReturnTagValueNode $returnTag) => $this->getType($returnTag->type),
            $this->phpDocNode->getReturnTagValues()
        );

        // remove null values
        return array_filter($returnTypes);
    }

    /**
     * @return array<array{type: string, line: int|null}>
     */
    public function getThrowTagsTypes(): array
    {
        $result = [];

        foreach ($this->phpDocNode->getTagsByName('@throws') as $tagNode) {
            if (!$tagNode instanceof PhpDocTagNode || !$tagNode->value instanceof ThrowsTagValueNode) {
                continue;
            }

            $type = $this->getType($tagNode->value->type);

            if (null === $type) {
                continue;
            }

            $result[] = [
                'type' => $type,
                'line' => $tagNode->getAttribute('startLine'),
            ];
        }

        return $result;
    }

    public function getVarTagTypes(): array
    {
        $varTypes = array_map(
            fn (VarTagValueNode $varTag) => $this->getType($varTag->type),
            $this->phpDocNode->getVarTagValues()
        );

        // remove null values
        return array_filter($varTypes);
    }

    public function getDoctrineLikeAnnotationTypes(): array
    {
        $doctrineAnnotations = [];

        foreach ($this->phpDocNode->getTags() as $tag) {
            if ('@' === $tag->name[0] && !str_contains($tag->name, '@var')) {
                $doctrineAnnotations[] = str_replace('@', '', $tag->name);
            }
        }

        return $doctrineAnnotations;
    }

    private function getType(TypeNode $typeNode): ?string
    {
        if ($typeNode instanceof IdentifierTypeNode) {
            // this handles ClassName
            return $typeNode->name;
        }

        if ($typeNode instanceof GenericTypeNode) {
            // this handles list<ClassName>
            if (1 === \count($typeNode->genericTypes)) {
                return (string) $typeNode->genericTypes[0];
            }

            // this handles array<int, ClassName>
            if (2 === \count($typeNode->genericTypes)) {
                return (string) $typeNode->genericTypes[1];
            }
        }

        // this handles ClassName[]
        if ($typeNode instanceof ArrayTypeNode) {
            return (string) $typeNode->type;
        }

        return null;
    }
}
