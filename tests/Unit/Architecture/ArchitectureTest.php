<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Architecture;

use Arkitect\Architecture\Architecture;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\TestCase;

class ArchitectureTest extends TestCase
{
    public function test_layered_architecture(): void
    {
        $rules = Architecture::withLayers()
            ->layer('Domain')->definedBy('App\*\Domain\*')
            ->layer('Application')->definedBy('App\*\Application\*')
            ->layer('Infrastructure')->definedBy('App\*\Infrastructure\*')

            ->where('Domain')->mayNotDependOnAnyLayer()
            ->where('Application')->mayDependOnLayers('Domain')
            ->where('Infrastructure')->mayDependOnLayers('Application', 'Domain')

            ->rules();

        $expectedRules = [
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
                ->should(new NotDependsOnTheseNamespaces('App\*\Application\*', 'App\*\Infrastructure\*'))
                ->because('of the layered architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Application\*'))
                ->should(new NotDependsOnTheseNamespaces('App\*\Infrastructure\*'))
                ->because('of the layered architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Infrastructure\*'))
                ->should(new NotDependsOnTheseNamespaces())
                ->because('of the layered architecture'),
        ];

        self::assertEquals($expectedRules, iterator_to_array($rules));
    }

    public function test_modular_architecture(): void
    {
        $rules = Architecture::withModules()
            ->module('CRM')->definedBy('App\CRM\*')
            ->module('InvoiceReconciliation')->definedBy('App\InvoiceReconciliation\*')
            ->module('Orders')->definedBy('App\Orders\*')
            ->module('Shared')->definedBy('App\Shared\*')
            ->module('Bridge')->definedBy('App\Bridge\*')

            ->where('Shared')->mayNotDependOnAnyModule()
            ->where('CRM')->mayDependOnModules('Shared')
            ->where('InvoiceReconciliation')->mayDependOnModules('Shared')
            ->where('Bridge')->mayDependOnAnyModule()
            ->where('Orders')->mayDependOnModules('Shared')

            ->rules();

        $expectedRules = [
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\CRM\*'))
                ->should(new NotDependsOnTheseNamespaces('App\InvoiceReconciliation\*', 'App\Orders\*', 'App\Bridge\*'))
                ->because('of the modular architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\InvoiceReconciliation\*'))
                ->should(new NotDependsOnTheseNamespaces('App\CRM\*', 'App\Orders\*', 'App\Bridge\*'))
                ->because('of the modular architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\Orders\*'))
                ->should(new NotDependsOnTheseNamespaces('App\CRM\*', 'App\InvoiceReconciliation\*', 'App\Bridge\*'))
                ->because('of the modular architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\Shared\*'))
                ->should(new NotDependsOnTheseNamespaces('App\CRM\*', 'App\InvoiceReconciliation\*', 'App\Orders\*', 'App\Bridge\*'))
                ->because('of the modular architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\Bridge\*'))
                ->should(new NotDependsOnTheseNamespaces())
                ->because('of the modular architecture'),
        ];

        self::assertEquals($expectedRules, iterator_to_array($rules));
    }
}
