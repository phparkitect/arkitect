<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration\PHPUnit;

use Arkitect\Expression\ForClasses\HaveTrait;
use Arkitect\Expression\ForClasses\NotHaveTrait;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class CheckClassHaveTraitTest extends TestCase
{
    public function test_feature_tests_should_use_database_transactions_trait(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Tests\Feature'))
            ->should(new HaveTrait('DatabaseTransactions'))
            ->because('we want all Feature tests to run transactions');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertEquals('Tests\Feature\UserFeatureTest', $runner->getViolations()->get(0)->getFqcn());
    }

    public function test_feature_tests_should_not_use_refresh_database_trait(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Tests\Feature'))
            ->should(new NotHaveTrait('RefreshDatabase'))
            ->because('we want all Feature tests to never refresh the database for performance reasons');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertEquals('Tests\Feature\ProductFeatureTest', $runner->getViolations()->get(0)->getFqcn());
    }

    public function test_classes_with_uuid_should_have_uuid_trait(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Models'))
            ->should(new HaveTrait('HasUuid'))
            ->because('all models should use UUID');

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());
    }

    public function createDirStructure(): array
    {
        return [
            'Feature' => [
                'OrderFeatureTest.php' => <<<'EOT'
                    <?php

                    namespace Tests\Feature;

                    use DatabaseTransactions;

                    class OrderFeatureTest
                    {
                        use DatabaseTransactions;
                    }
                    EOT,
                'ProductFeatureTest.php' => <<<'EOT'
                    <?php

                    namespace Tests\Feature;

                    use DatabaseTransactions;
                    use RefreshDatabase;

                    class ProductFeatureTest
                    {
                        use DatabaseTransactions;
                        use RefreshDatabase;
                    }
                    EOT,
                'UserFeatureTest.php' => <<<'EOT'
                    <?php

                    namespace Tests\Feature;

                    class UserFeatureTest
                    {
                        // Missing DatabaseTransactions trait
                    }
                    EOT,
            ],
            'Models' => [
                'User.php' => <<<'EOT'
                    <?php

                    namespace App\Models;

                    use HasUuid;

                    class User
                    {
                        use HasUuid;
                    }
                    EOT,
                'Product.php' => <<<'EOT'
                    <?php

                    namespace App\Models;

                    use HasUuid;

                    class Product
                    {
                        use HasUuid;
                    }
                    EOT,
            ],
        ];
    }
}
