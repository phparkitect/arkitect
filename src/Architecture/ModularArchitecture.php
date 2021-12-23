<?php
declare(strict_types=1);

namespace Arkitect\Architecture;

use Arkitect\Architecture\DSL\Modular\DefinedBy;
use Arkitect\Architecture\DSL\Modular\MayDependOnAnyModule;
use Arkitect\Architecture\DSL\Modular\MayDependOnModules;
use Arkitect\Architecture\DSL\Modular\MayNotDependOnAnyModule;
use Arkitect\Architecture\DSL\Modular\Module;
use Arkitect\Architecture\DSL\Modular\Rules;
use Arkitect\Architecture\DSL\Modular\Where;

class ModularArchitecture implements Module, DefinedBy, Where, MayNotDependOnAnyModule, MayDependOnModules, MayDependOnAnyModule, Rules
{
    /** @var Architecture */
    private $architecture;

    public function __construct(Architecture $architecture)
    {
        $this->architecture = $architecture;
    }

    public function module(string $name): DefinedBy
    {
        $this->architecture->component($name);

        return $this;
    }

    public function definedBy(string $selector): self
    {
        $this->architecture->definedBy($selector);

        return $this;
    }

    public function where(string $moduleName): self
    {
        $this->architecture->where($moduleName);

        return $this;
    }

    public function mayNotDependOnAnyModule(): self
    {
        $this->architecture->mayNotDependOnAnyComponent();

        return $this;
    }

    public function mayDependOnModules(string ...$moduleNames): self
    {
        $this->architecture->mayDependOnComponents(...$moduleNames);

        return $this;
    }

    public function mayDependOnAnyModule(): self
    {
        $this->architecture->mayDependOnAnyComponent();

        return $this;
    }

    public function rules(): iterable
    {
        yield from $this->architecture->rulesBecause('of the modular architecture');
    }
}
