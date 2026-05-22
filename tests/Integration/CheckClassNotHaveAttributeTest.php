<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration\PHPUnit;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\NotHaveAttribute;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class CheckClassNotHaveAttributeTest extends TestCase
{
    public function test_controllers_should_not_have_deprecated_attribute(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller'))
            ->should(new NotHaveAttribute('Deprecated'))
            ->because('deprecated controllers should be removed, not kept in production');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertEquals('App\Controller\LegacyController', $runner->getViolations()->get(0)->getFqcn());
    }

    public function test_models_without_prohibited_attribute_pass(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveNameMatching('*Model'))
            ->should(new NotHaveAttribute('Deprecated'))
            ->because('deprecated models should be removed');

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());
    }

    public function createDirStructure(): array
    {
        return [
            'Controller' => [
                'ProductsController.php' => <<<'EOT'
                    <?php

                    namespace App\Controller;

                    #[\AsController]
                    class ProductsController
                    {
                    }
                    EOT,
                'LegacyController.php' => <<<'EOT'
                    <?php

                    namespace App\Controller;

                    #[\Deprecated]
                    #[\AsController]
                    class LegacyController
                    {
                    }
                    EOT,
            ],
            'Model' => [
                'UserModel.php' => <<<'EOT'
                    <?php

                    namespace App\Model;

                    #[\Entity]
                    class UserModel
                    {
                    }
                    EOT,
            ],
        ];
    }
}
