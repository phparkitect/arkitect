<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

/**
 * Whether a class is one the rule talks about.
 *
 * `Unresolved` is not a third kind of "no". A selector that walks the
 * inheritance chain can hit an ancestor that was never parsed, and then
 * neither answer is honest: including the class means checking something
 * we can't vouch for, excluding it means dropping it in silence. Saying so
 * lets `Rule` record it the same way a constraint's unresolved outcome is
 * recorded, instead of guessing.
 */
enum Selection
{
    case Yes;
    case No;
    case Unresolved;
}
