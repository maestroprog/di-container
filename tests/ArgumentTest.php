<?php

namespace Maestroprog\Container\Tests;

use Maestroprog\Container\Argument;
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    public function testArgumentParsing()
    {
        $modifiers = [
            'internal' => '',
            'private' => '',
            'decorates' => 'class',
        ];
        $argument = new Argument('string', $modifiers);
        $this->assertTrue($argument->isInternal());
        $this->assertTrue($argument->isDecorator());
        $this->assertEquals($modifiers['decorates'], $argument->getDecoratorArguments());
        $modifiers = [];
        $argument = new Argument('string', $modifiers);
        $this->assertFalse($argument->isInternal());
        $this->assertFalse($argument->isDecorator());
        $this->assertEquals('', $argument->getDecoratorArguments());
    }
}
