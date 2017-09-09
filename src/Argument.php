<?php

namespace Maestroprog\Container;

class Argument
{
    private $returnType;
    private $body;
    private $modifiers;

    /**
     * @param string $returnType
     * @param string $body
     * @param string[] $modifiers
     */
    public function __construct(string $returnType, string $body, array $modifiers)
    {
        $this->returnType = $returnType;
        $this->body = $body;
        $this->modifiers = $modifiers;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
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
