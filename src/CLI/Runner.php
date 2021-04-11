<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\FileParser;
use Arkitect\ClassSetRules;
use Arkitect\Rules\Violations;
use Symfony\Component\Finder\SplFileInfo;

class Runner
{
    public function run(Config $config, Progress $progress): Violations
    {
        $fileParser = new FileParser();
        $violations = new Violations();

        /** @var ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            $progress->startFileSetAnalysis($classSetRule->getClassSet());

            $this->check($classSetRule, $progress, $fileParser, $violations);

            $progress->endFileSetAnalysis($classSetRule->getClassSet());
        }

        return $violations;
    }

    public function check(ClassSetRules $classSetRule, Progress $progress, FileParser $fileParser, Violations $violations): void
    {
        /** @var SplFileInfo $file */
        foreach ($classSetRule->getClassSet() as $file) {
            $progress->startParsingFile($file->getRelativePathname());

            $fileParser->parse($file->getContents());

            foreach ($fileParser->getClassDescriptions() as $classDescription) {
                foreach ($classSetRule->getRules() as $rule) {
                    $rule->check($classDescription, $violations);
                }
            }

            $progress->endParsingFile($file->getRelativePathname());
        }
    }
}
