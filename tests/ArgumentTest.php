<?php

namespace Maestroprog\Container\Tests;

use Maestroprog\Container\Argument;
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    public function testArgumentParsing(): void
    {
        $modifiers = [
            'internal' => '',
            'private' => '',
            'decorates' => 'class',
        ];
        $argument = new Argument('getA', 'string', $modifiers);
        $this->assertTrue($argument->isInternal());
        $this->assertTrue($argument->isDecorator());
        $this->assertEquals($modifiers['decorates'], $argument->getDecoratedService());
        $this->assertEquals('getA', $argument->getMethodName());
        $modifiers = [];
        $argument = new Argument('getB', 'string', $modifiers);
        $this->assertFalse($argument->isInternal());
        $this->assertFalse($argument->isDecorator());
        $this->assertEquals('', $argument->getDecoratedService());
        $this->assertEquals('getB', $argument->getMethodName());

        $this->assertEquals('string', (string)$argument);
    }
}
