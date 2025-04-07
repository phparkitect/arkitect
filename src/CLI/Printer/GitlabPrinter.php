<?php
declare(strict_types=1);

namespace Arkitect\CLI\Printer;

use Arkitect\Rules\Violation;

class GitlabPrinter implements Printer
{
    public function print(array $violationsCollection): string
    {
        $allErrors = [];

        /**
         * @var string      $key
         * @var Violation[] $violationsByFqcn
         */
        foreach ($violationsCollection as $class => $violationsByFqcn) {
            foreach ($violationsByFqcn as $violation) {
                $checkName = $class.'.'.$this->toKebabCase($violation->getError());

                $error = [
                    'description' => $violation->getError(),
                    'check_name' => $checkName,
                    'fingerprint' => hash('sha256', $checkName),
                    'severity' => 'major',
                    'location' => [
                        'path' => $violation->getFilePath(),
                        'lines' => [
                            'begin' => $violation->getLine() ?? 1,
                        ],
                    ],
                ];

                $allErrors[] = $error;
            }
        }

        return json_encode($allErrors);
    }

    private function toKebabCase(string $string): string
    {
        $string = preg_replace('/[^a-zA-Z0-9]+/', ' ', $string);
        $string = preg_replace('/\s+/', ' ', $string);
        $string = strtolower(trim($string));
        $string = str_replace(' ', '-', $string);

        return $string;
    }
}
