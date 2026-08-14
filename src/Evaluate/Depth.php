<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * How far up the inheritance chain a relationship check looks.
 *
 * `Transitive` is the default everywhere it appears: a class that inherits
 * an interface from its parent really does implement it, so answering from
 * the declaration alone would report false violations on the most ordinary
 * code there is. `Direct` is for the rarer rule that constrains the shape
 * of the declaration itself, not the resulting type.
 */
enum Depth
{
    case Transitive;
    case Direct;
}
