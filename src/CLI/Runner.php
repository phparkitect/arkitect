<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\Progress;
use Arkitect\Rules\Violations;

class Runner
{
    public function run(Config $config, Progress $progress): Violations
    {
        /** @var FileParser $fileParser */
        $fileParser = FileParserFactory::createFileParser();
        $violations = new Violations();

        /** @var ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            $progress->startFileSetAnalysis($classSetRule->getClassSet());

            $this->check($classSetRule, $progress, $fileParser, $violations);

            $progress->endFileSetAnalysis($classSetRule->getClassSet());
        }

        return $violations;
    }

    public function check(ClassSetRules $classSetRule, Progress $progress, Parser $fileParser, Violations $violations): void
    {
        $classDescriptionsFiles = GetClassDescriptions::execute($classSetRule, $fileParser);

        /** @var ClassDescription $classDescription */
        foreach ($classDescriptionsFiles as $classDescription) {
            $progress->startParsingFile($classDescription->getName());

            if ($this->classExtendsOtherClass($classDescription, $classDescriptionsFiles)) {
                $classDescription = $this->enrichClassDescription($classDescription, $classDescriptionsFiles);
            }

            $this->ruleCheckAndEndParsingFile($classSetRule, $classDescription, $violations, $progress);
        }
    }

    private function ruleCheckAndEndParsingFile(
        ClassSetRules $classSetRule,
        ClassDescription $classDescription,
        Violations $violations,
        Progress $progress
    ): void {
        foreach ($classSetRule->getRules() as $rule) {
            $rule->check($classDescription, $violations);
        }

        $progress->endParsingFile($classDescription->getName());
    }

    private function classExtendsOtherClass(ClassDescription $classDescription, array $classDescriptionsFiles): bool
    {
        $extends = $classDescription->getExtends();
        if (null === $extends) {
            return false;
        }

        /** @var FullyQualifiedClassName $extends */
        $extendedClassFqcn = $extends->toString();
        if (!isset($classDescriptionsFiles[$extendedClassFqcn])) {
            return false;
        }

        return true;
    }

    private function enrichClassDescription(ClassDescription $classDescription, array $classDescriptionsFiles): ClassDescription
    {
        /** @var FullyQualifiedClassName $extends */
        $extends = $classDescription->getExtends();
        $extendedClassFqcn = $extends->toString();

        /** @var ClassDescription $extendedClass */
        $extendedClassDescriptions = $classDescriptionsFiles[$extendedClassFqcn];
        $classDescription->addDependencies($extendedClassDescriptions->getDependencies());
        $classDescription->addInterfaces($extendedClassDescriptions->getInterfaces());

        return $classDescription;
    }
}
