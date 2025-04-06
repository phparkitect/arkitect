<?php
declare(strict_types=1);

namespace Arkitect\CLI\Printer;

use Arkitect\Rules\Violation;

class JsonPrinter implements Printer
{
    public function print(array $violationsCollection): string
    {
        $totalViolations = 0;
        $details = [];

        /**
         * @var string           $key
         * @var array<Violation> $violationsByFqcn
         */
        foreach ($violationsCollection as $class => $violationsByFqcn) {
            $violationForThisFqcn = \count($violationsByFqcn);
            $totalViolations += $violationForThisFqcn;

            $details[$class] = [];

            foreach ($violationsByFqcn as $key => $violation) {
                $details[$class][$key]['error'] = $violation->getError();

                if (null !== $violation->getLine()) {
                    $details[$class][$key]['line'] = $violation->getLine();
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
