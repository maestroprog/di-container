<?php

declare(strict_types=1);

namespace Qwerty\Container\Tests;

use PHPUnit\Framework\TestCase;
use Qwerty\Container\AbstractBasicContainer;
use Qwerty\Container\Container;

class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    protected function setUp()
    {
        $this->container = Container::instance();
    }

    public function testContainer()
    {
        $this->container->register(new MyContainer());

        /** @var MyService2 $service2 */
        $service2 = $this->container->get(MyService2::class);

        $service1 = $service2->getService1();

        $this->assertEquals($service1, $this->container->get(MyService1::class));
    }
}

class MyService1
{

}

class MyService2
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
    protected function getMyService1(): MyService1
    {
        return new MyService1();
    }

    protected function getMyService2(): MyService2
    {
        return new MyService2($this->get(MyService1::class));
    }
}
