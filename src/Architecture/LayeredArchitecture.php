<?php
declare(strict_types=1);

namespace Arkitect\Architecture;

use Arkitect\Architecture\DSL\Layered\DefinedBy;
use Arkitect\Architecture\DSL\Layered\Layer;
use Arkitect\Architecture\DSL\Layered\MayDependOnAnyLayer;
use Arkitect\Architecture\DSL\Layered\MayDependOnLayers;
use Arkitect\Architecture\DSL\Layered\Rules;
use Arkitect\Architecture\DSL\Layered\ShouldNotDependOnAnyLayer;
use Arkitect\Architecture\DSL\Layered\Where;

class LayeredArchitecture implements Layer, DefinedBy, Where, ShouldNotDependOnAnyLayer, MayDependOnLayers, MayDependOnAnyLayer, Rules
{
    /** @var Architecture */
    private $architecture;

    public function __construct(Architecture $architecture)
    {
        $this->architecture = $architecture;
    }

    public function layer(string $name): DefinedBy
    {
        $this->architecture->component($name);

        return $this;
    }

    public function definedBy(string $selector): self
    {
        $this->architecture->definedBy($selector);

        return $this;
    }

    public function where(string $layerName): self
    {
        $this->architecture->where($layerName);

        return $this;
    }

    public function shouldNotDependOnAnyLayer(): self
    {
        $this->architecture->shouldNotDependOnAnyComponent();

        return $this;
    }

    public function mayDependOnLayers(string ...$layerNames): self
    {
        $this->architecture->mayDependOnComponents(...$layerNames);

        return $this;
    }

    public function mayDependOnAnyLayer(): self
    {
        $this->architecture->mayDependOnAnyComponent();

        return $this;
    }

    public function rules(): iterable
    {
        yield from $this->architecture->rulesBecause('of the layered architecture');
    }
}
