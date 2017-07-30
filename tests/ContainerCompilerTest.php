<?php

declare(strict_types=1);

namespace Qwerty\Container\Tests;

use PHPUnit\Framework\TestCase;
use Qwerty\Container\AbstractBasicContainer;
use Qwerty\Container\AbstractCompiledContainer;
use Qwerty\Container\Container;
use Qwerty\Container\ContainerCompiler;

class ContainerCompilerTest extends TestCase
{
    public function testCompile()
    {
        $container = Container::instance();
        $container->register(new MyContainer());
        $compiler = new ContainerCompiler($container);
        $compiler->compile($php = tempnam(sys_get_temp_dir(), 'compiler'));
        $this->assertFileExists($php);
        require_once $php;

        $this->assertInstanceOf(AbstractCompiledContainer::class, \CompiledContainer::class);
        try {
            (new \ReflectionClass(\CompiledContainer::class))->getMethod('getMyService1');
        } catch (\ReflectionException $e) {
            $this->assertTrue(false, 'Method getMyService1 does not exists.');
        }
        unlink($php);
    }
}

class MyContainer2 extends AbstractBasicContainer
{
    public function getMyServiceOff(): MyService1
    {
        return new MyService1(false);
    }

    public function getMyServiceOn(): MyService1
    {
        return new MyService1(true);
    }
}
