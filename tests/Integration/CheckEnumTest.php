<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsEnum;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class CheckEnumTest extends TestCase
{
    public function test_naming_is_enforced(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.4');

        $rules = [];

        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Modules\*\App\Domains\*\Enums'))
            ->should(new HaveNameMatching('*Enum'))
            ->because('we want uniform naming');

        $rules[] = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Modules\*\App\Domains\*\Enums'))
            ->should(new IsEnum())
            ->because('Enums should be enums');

        $runner->run($dir, ...$rules);

        self::assertCount(2, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertStringContainsString('should have a name that matches *Enum because', $runner->getViolations()->get(0)->getError());
        self::assertStringContainsString('Aclass should be an enum because Enums should be enums', $runner->getViolations()->get(1)->getError());
    }

    public function createDirStructure(): array
    {
        return [
            'Modules' => [
                'Core' => [
                    'App' => [
                        'Domains' => [
                            'Base' => [
                                'Enums' => [
                                    'BillingEnum.php' => '<?php

                                    namespace Modules\Core\App\Domains\Base\Enums;

                                    enum BillingEnum {
                                        case PENDING;
                                        case PAID;
                                    }
                                    ',
                                    'GenderEnum.php' => '<?php

                                    namespace Modules\Core\App\Domains\Base\Enums;

                                    enum GenderEnum {
                                        case MALE;
                                        case FEMALE;
                                    }
                                    ',
                                    'AClass.php' => '<?php

                                    namespace Modules\Core\App\Domains\Base\Enums;

                                    class Aclass { }
                                    ',
                                ],
                                'Traits' => [
                                    'OneTrait.php' => '<?php

                                    namespace Modules\Core\App\Domains\Base\Traits;

                                    use Modules\Core\App\Domains\Base\Enums;

                                    trait OneTrait {
                                        public function one(GenderEnum $gender) {}
                                    }
                                    ',
                                    'TwoTrait.php' => '<?php

                                    namespace Modules\Core\App\Domains\Base\Traits;

                                    use Modules\Core\App\Domains\Base\Enums;

                                    trait TwoTrait {
                                        public function one(GenderEnum $gender) {}
                                    }
                                    ',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
