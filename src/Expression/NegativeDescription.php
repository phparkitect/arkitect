<?php
declare(strict_types=1);

namespace Arkitect\Expression;

class NegativeDescription implements Description
{
    /** @var Description */
    private $description;

    public function __construct(Description $description)
    {
        $this->description = $description;
    }

    public function toString(): string
    {
        return $this->toNegative();
    }

    public function getPattern(): void
    {
        $this->description->getPattern();
    }

    protected function toNegative(): string
    {
        //looking for the pattern [positive | negative] in the string and take the negative
        $replaced = preg_replace('/\[(.+)\|(.+)]/i', '$2', $this->description->getPattern());

        return ((bool) $replaced) ? $replaced : $this->description->toString();
    }
}
