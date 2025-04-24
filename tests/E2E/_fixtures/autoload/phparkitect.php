<?php
declare(strict_types=1);

use Arkitect\Analyzer\ClassDescription;
use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

return static function (Config $config): void {
    // a dummy rule to check if the class is autoloaded
    $autoload_rule = new class('Autoload\Model\UserInterface') implements Expression {
        public string $implements;

        public function __construct(string $implements)
        {
            $this->implements = $implements;
        }

        public function describe(ClassDescription $theClass, string $because): Description
        {
            return new Description("{$theClass->getFQCN()} should implement {$this->implements}", $because);
        }

        public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
        {
            if (is_a($theClass->getFQCN(), $this->implements, true)) {
                return;
            }

            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            );

            $violations->add($violation);
        }
    };

    $mvc_class_set = ClassSet::fromDir(__DIR__.'/src');

    $rule = Rule::allClasses()
        ->except('Autoload\Model\UserInterface')
        ->that(new ResideInOneOfTheseNamespaces('Autoload\Model'))
        ->should($autoload_rule)
        ->because('we want check if the class is autoloaded');

    $config
        ->add($mvc_class_set, $rule);
};
