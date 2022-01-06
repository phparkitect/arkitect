<?php

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;

class HaveNameStartsWith implements Expression
{
	/** @var string */
	private $name;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function describe(ClassDescription $theClass): Description
	{
		return new PositiveDescription("should have a name that starts with {$this->name}");
	}

	public function evaluate(ClassDescription $theClass, Violations $violations): void
	{
		$fqcn = FullyQualifiedClassName::fromString($theClass->getFQCN());

		if (!str_starts_with($fqcn->className(), trim($this->name))) {
			$violation = Violation::create(
				$theClass->getFQCN(),
				$this->describe($theClass)->toString()
			);

			$violations->add($violation);
		}
	}
}
