<?php
declare(strict_types=1);


namespace Arkitect\Analyzer;

class PatternString
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function matches(string $pattern): bool
    {
        $pattern = strtr($pattern, ['\\' => '_', '**' => '(.+)',  '*' => '([a-zA-Z0-9]+)']);
        $name = strtr($this->value, ['\\' => '_']);

        return (bool) preg_match("/^$pattern$/", $name);
    }

    public function explode(string $delimiter): array
    {
        return explode($delimiter, $this->value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
