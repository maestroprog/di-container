<?php

namespace Maestroprog\Container;

class Argument
{
    private $returnType;
    private $modifiers;

    /**
     * @param string $returnType
     * @param string[] $modifiers
     */
    public function __construct(string $returnType, array $modifiers)
    {
        $this->returnType = $returnType;
        $this->modifiers = $modifiers;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function isInternal(): bool
    {
        return array_key_exists('internal', $this->modifiers);
    }

    public function isDecorator(): bool
    {
        return array_key_exists('decorates', $this->modifiers);
    }

    public function getDecoratorArguments(): string
    {
        return $this->getArgumentsForModifier('decorates');
    }

    public function getArgumentsForModifier(string $key): string
    {
        return $this->modifiers[$key] ?? '';
    }

    public function __toString()
    {
        return $this->returnType;
    }
}
