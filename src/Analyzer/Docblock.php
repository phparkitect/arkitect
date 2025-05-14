<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
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

    private function getType(TypeNode $typeNode): ?string
    {
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
