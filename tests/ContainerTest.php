<?php

declare(strict_types=1);

namespace Maestroprog\Container\Tests;

use Maestroprog\Container\HasPriorityInterface;
use Maestroprog\Container\NotFoundException;
use PHPUnit\Framework\TestCase;
use Maestroprog\Container\AbstractBasicContainer;
use Maestroprog\Container\Container;

/**
 * @covers \Maestroprog\Container\Argument
 * @covers \Maestroprog\Container\HasPriorityInterface
 * @covers \Maestroprog\Container\IterableContainerInterface
 * @covers \Maestroprog\Container\NotFoundException
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

        $this->container->get(MyService2::class);

        /** @var MyService2 $service2 */
        $service2 = $this->container->get(MyService2::class);

        $service2->getService1();
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
        $container2 = new class extends AbstractBasicContainer implements HasPriorityInterface
        {
            public function getMyService1(): MyService1
            {
                return new MyService1(true);
            }

            public function priority(): int
            {
                return 2;
            }
        };
        $this->container->register(new MyContainer());
        $this->container->register($container2);
        $this->assertInstanceOf(MyServiceInterface::class, $container2->getMyService1());
    }

    public function testLowPriorityContainer()
    {
        $container2 = new class extends AbstractBasicContainer implements HasPriorityInterface
        {
            public function getMyService1(): MyService1
            {
                return new MyService1(true);
            }

            public function priority(): int
            {
                return -1;
            }
        };
        $this->container->register(new MyContainer());
        $this->container->register($container2);
        $this->assertInstanceOf(MyServiceInterface::class, $container2->getMyService1());
    }

    public function testHasNoGlobalContainer()
    {
        $this->expectException(\RuntimeException::class);
        $container = new MyContainer();
        $container->get('don\'t know');
    }

    public function testUsingGlobalContainer()
    {
        $this->expectException(NotFoundException::class);
        $container = new MyContainer();
        $this->container->register($container);
        $container->get('don\'t know');
    }

    public function testContainerMagicCall()
    {
        $this->container->register(new MyContainer());
        $service1 = $this->container->getMyService1();
        $this->assertInstanceOf(MyService1::class, $service1);
    }

    public function testContainerInvalidMagicCall()
    {
        $this->expectException(\RuntimeException::class);
        $this->container->invalid();
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
