<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;
use Arkitect\Expression\Expression;
use Arkitect\Expression\Description;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\PositiveDescription;

class HaveNameEndsWith implements Expression
{
	/** @var string */
	private $name;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function describe(ClassDescription $theClass): Description
	{
		return new PositiveDescription("should have a name that ends with {$this->name}");
	}

	public function evaluate(ClassDescription $theClass, Violations $violations): void
	{
		$fqcn = FullyQualifiedClassName::fromString($theClass->getFQCN());

		if (!(strpos($fqcn->className(), $this->name) != 0)) {
			$violation = Violation::create(
				$theClass->getFQCN(),
				$this->describe($theClass)->toString()
			);

			$violations->add($violation);
		}
	}
}