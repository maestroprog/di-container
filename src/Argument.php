<?php

declare(strict_types=1);

namespace Maestroprog\Container;

class Argument
{
    private $methodName;
    private $returnType;
    private $modifiers;

    /**
     * @param string $methodName
     * @param string $returnType
     * @param string[] $modifiers
     */
    public function __construct(string $methodName, string $returnType, array $modifiers)
    {
        $this->methodName = $methodName;
        $this->returnType = $returnType;
        $this->modifiers = $modifiers;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
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

    public function getDecoratedService(): string
    {
        return $this->getArgumentsForModifier('decorates');
    }

    public function getArgumentsForModifier(string $key): string
    {
        return $this->modifiers[$key] ?? '';
    }

    public function __toString(): string
    {
        return $this->returnType;
    }
}
