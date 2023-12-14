<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

final class IsMapped implements Expression
{
    public const POSITIVE_DESCRIPTION = 'should exist in the list';

    /** @var array */
    private $list;

    public function __construct(array $list)
    {
        $this->list = array_flip($list);
    }

    public function describe(ClassDescription $theClass, string $because = ''): Description
    {
        return new Description(self::POSITIVE_DESCRIPTION, $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because = ''): void
    {
        if (isset($this->list[$theClass->getFQCN()])) {
            return;
        }

        $violation = Violation::create(
            $theClass->getFQCN(),
            ViolationMessage::selfExplanatory($this->describe($theClass, $because))
        );

        $violations->add($violation);
    }
}
