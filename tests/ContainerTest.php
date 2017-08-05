<?php

declare(strict_types=1);

namespace Maestroprog\Container\Tests;

use Maestroprog\Container\IterableContainerInterface;
use Maestroprog\Container\NotFoundException;
use PHPUnit\Framework\TestCase;
use Maestroprog\Container\AbstractBasicContainer;
use Maestroprog\Container\Container;

/**
 * @covers \Maestroprog\Container\Container
 * @covers \Maestroprog\Container\ContainerCompiler
 * @covers \Maestroprog\Container\AbstractBasicContainer
 * @covers \Maestroprog\Container\AbstractCompiledContainer
 */
class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    protected function setUp()
    {
        $this->container = clone Container::instance();
    }

    public function testContainer()
    {
        $this->container->register(new MyContainer());

        /** @var MyService2 $service2 */
        $service2 = $this->container->get(MyService2::class);

        $service1 = $service2->getService1();

        $this->assertEquals($service1, $this->container->get(MyService1::class));
    }

    public function testInvalidOverrideContainer()
    {
        $container2 = new class extends MyContainer
        {
            public function getMyService1(): MyService1
            {
                return parent::getMyService1();
            }
        };

        $this->expectException(\LogicException::class);
        $this->container->register(new MyContainer());
        $this->container->register($container2);
    }

    public function testInvalidTypesOverrideContainer()
    {
        $container2 = new class extends AbstractBasicContainer
        {
            public function getMyService2(): MyService1
            {
                return new MyService1(false);
            }
        };
        $this->expectException(\LogicException::class);
        $this->container->register(new MyContainer());
        $this->container->register($container2);
    }

    public function testNotFoundService()
    {
        $this->expectException(NotFoundException::class);
        $this->container->get('unknownService');
    }

    public function testServiceWithCommonInterface()
    {
        $container2 = new class extends AbstractBasicContainer
        {
            public function getMyService1(): MyServiceInterface
            {
                return new MyService1(true);
            }
        };
        $this->container->register(new MyContainer());
        $this->container->register($container2);
    }
}

class MyService1 implements MyServiceInterface
{
    private $enabled;

    public function __construct(bool $enable)
    {
        $this->enabled = $enable;
    }
}

class MyService2 implements MyServiceInterface
{
    private $service1;

    public function __construct(MyService1 $service1)
    {
        $this->service1 = $service1;
    }

    public function getService1(): MyService1
    {
        return $this->service1;
    }
}

class MyContainer extends AbstractBasicContainer
{
    public function getMyService1(): MyService1
    {
        return new MyService1(true);
    }

    public function getMyService2(): MyService2
    {
        return new MyService2($this->get(MyService1::class));
    }
}

interface MyServiceInterface
{

}
