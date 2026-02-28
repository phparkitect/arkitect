<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration\PHPUnit;

use Arkitect\Expression\ForClasses\HaveAttribute;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class CheckClassHaveAttributeTest extends TestCase
{
    public function test_models_should_reside_in_app_model(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveAttribute('Entity'))
            ->should(new ResideInOneOfTheseNamespaces('App\Model'))
            ->because('we use an ORM');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
    }

    public function test_controllers_should_have_name_ending_in_controller(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveAttribute('AsController'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('its a symfony thing');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());

        self::assertEquals('App\Controller\Foo', $runner->getViolations()->get(0)->getFqcn());
    }

    public function test_controllers_should_have_controller_attribute(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveNameMatching('*Controller'))
            ->should(new HaveAttribute('AsController'))
            ->because('it configures the service container');

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getViolations());
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
                'Foo.php' => <<<'EOT'
                    <?php

                    namespace App\Controller;

                    #[\AsController]
                    class Foo
                    {
                    }
                    EOT,
                'User.php' => <<<'EOT'
                    <?php

                    namespace App\Controller;

                    #[\Entity]
                    class Product
                    {
                    }
                    EOT,
            ],
            'Model' => [
                'User.php' => <<<'EOT'
                    <?php

                    namespace App\Model;

                    #[\Entity]
                    class User
                    {
                    }
                    EOT,
            ],
        ];
    }
}
