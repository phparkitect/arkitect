<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSetRules;
use Symfony\Component\Finder\SplFileInfo;

class GetClassDescriptions
{
    public static function execute(ClassSetRules $classSetRule, Parser $fileParser): array
    {
        $classDescriptions = [];
        /** @var SplFileInfo $file */
        foreach ($classSetRule->getClassSet() as $file) {
            $fileParser->parse($file->getContents());
            $classDescriptionsFile = $fileParser->getClassDescriptions();
            /** @var ClassDescription $classDescription */
            foreach ($classDescriptionsFile as $classDescription) {
                $classDescriptions[$classDescription->getFQCN()] = $classDescription;
            }
        }

        return $classDescriptions;
    }
}
