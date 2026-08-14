<?php

declare(strict_types=1);

namespace Arkitect\Parser;

enum ClassKind
{
    case RegularClass;
    case Interface;
    case Trait;
    case Enum;
}
