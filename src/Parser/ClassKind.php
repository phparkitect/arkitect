<?php

declare(strict_types=1);

namespace Arkitect\Parser;

/**
 * A class-like declaration is exactly one of these, never a combination —
 * unlike three separate booleans, this can't represent "both an interface
 * and a trait".
 */
enum ClassKind
{
    case RegularClass;
    case Interface;
    case Trait;
    case Enum;
}
