<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class FileVisitorTest2 extends TestCase
{
    // TODO: temp test, remove!
    public function test_it_handles_typed_arrays(): void
    {
        $code = <<< 'EOF'
<?php
namespace Domain\Foo;

use Application\MyDto;

class MyClass
{
    /**
     * @var array<int, MyDto>
     */
    private array $dtoList;
}
EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }
}
