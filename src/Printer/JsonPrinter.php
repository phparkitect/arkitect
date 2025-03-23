<?php
declare(strict_types=1);

namespace Arkitect\Printer;

use Arkitect\Rules\Violation;

class JsonPrinter implements Printer
{
    public function print(array $violationsCollection): string
    {
        $totalViolations = 0;
        $details = [];

        /**
         * @var string      $key
         * @var Violation[] $violationsByFqcn
         */
        foreach ($violationsCollection as $key => $violationsByFqcn) {
            $violationForThisFqcn = \count($violationsByFqcn);
            $totalViolations += $violationForThisFqcn;

            $details[$key] = [];

            foreach ($violationsByFqcn as $kv => $violation) {
                $details[$key][$kv]['error'] = $violation->getError();

                if (null !== $violation->getLine()) {
                    $details[$key][$kv]['line'] = $violation->getLine();
                }
            }
        }

        $errors = [
            'totalViolations' => $totalViolations,
            'details' => $details,
        ];

        return json_encode($errors);
    }
}
