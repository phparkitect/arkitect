<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class CanParseTraitsTest extends TestCase
{
    public function test_it_parse_traits(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace MyProject\AppBundle\Application;

        use Doctrine\ORM\QueryBuilder;

        trait BookRepositoryInterface
        {
            public function getBookList(): QueryBuilder
            {

            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['MyProject\AppBundle\Application']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }
}
