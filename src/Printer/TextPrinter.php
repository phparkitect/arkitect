<?php
declare(strict_types=1);

namespace Arkitect\Printer;

use Arkitect\Rules\Violation;

class TextPrinter implements Printer
{
    public function print(array $violationsCollection): string
    {
        $errors = '';

        /**
         * @var string      $key
         * @var Violation[] $violationsByFqcn
         */
        foreach ($violationsCollection as $key => $violationsByFqcn) {
            $violationForThisFqcn = \count($violationsByFqcn);
            $errors .= "\n$key has {$violationForThisFqcn} violations";

            foreach ($violationsByFqcn as $violation) {
                $errors .= "\n  ".$violation->getError();

                if (null !== $violation->getLine()) {
                    $errors .= ' (on line '.$violation->getLine().')';
                }
            }
            $errors .= "\n";
        }

        return $errors;
    }
}
