<?php
declare(strict_types=1);

namespace Arkitect\CLI\Printer;

use Arkitect\Rules\Violation;

class GitlabPrinter implements Printer
{
    public function print(array $violationsCollection): string
    {
        $details = [];
        $errorClassGrouped = [];

        /**
         * @var string      $key
         * @var Violation[] $violationsByFqcn
         */
        foreach ($violationsCollection as $class => $violationsByFqcn) {
            foreach ($violationsByFqcn as $key => $violation) {
                /** @var class-string $fqcn */
                $fqcn = $violation->getFqcn();

                $errorClassGrouped[$class][$key]['description'] = $violation->getError();
                $errorClassGrouped[$class][$key]['check_name'] = $class.'.'.$this->toKebabCase($violation->getError());
                $errorClassGrouped[$class][$key]['fingerprint'] = hash('sha256', $errorClassGrouped[$class][$key]['check_name']);
                $errorClassGrouped[$class][$key]['severity'] = 'major'; // Todo enable severity on violation
                $errorClassGrouped[$class][$key]['location']['path'] = $this->getPathFromFqcn($fqcn);

                if (null !== $violation->getLine()) {
                    $errorClassGrouped[$class][$key]['lines']['begin'] = $violation->getLine();
                } else {
                    $errorClassGrouped[$class][$key]['lines']['begin'] = 1;
                }
            }
        }

        return json_encode(array_merge($details, ...array_values($errorClassGrouped)));
    }

    /**
     * @param class-string $fqcn
     */
    private function getPathFromFqcn(string $fqcn): string
    {
        return (new \ReflectionClass($fqcn))->getFileName();
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
