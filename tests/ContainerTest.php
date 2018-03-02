<?php

declare(strict_types=1);

namespace Maestroprog\Container\Tests;

use Maestroprog\Container\Container;
use Maestroprog\Container\ContainerException;
use Maestroprog\Container\HasContainerLinkInterface;
use Maestroprog\Container\HasPriorityInterface;
use Maestroprog\Container\NotFoundException;
use Maestroprog\Container\WithContainerLinkTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Maestroprog\Container\Argument
 * @covers \Maestroprog\Container\HasPriorityInterface
 * @covers \Maestroprog\Container\IterableContainerInterface
 * @covers \Maestroprog\Container\NotFoundException
 * @covers \Maestroprog\Container\Container
 * @covers \Maestroprog\Container\WithContainerLinkTrait
 * @covers \Maestroprog\Container\ServicesExtractor
 */
class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    protected function setUp()
    {
        $this->container = new Container();
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
        $this->assertEquals($this->container->get(MyService1::class), $this->container->get(MyService1::class));
        $this->assertEquals($service1, $this->container->get('MyService1'));
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
        $container2 = new class implements HasContainerLinkInterface
        {
            use WithContainerLinkTrait;

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
        $container2 = new class implements HasPriorityInterface, HasContainerLinkInterface
        {
            use WithContainerLinkTrait;

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
        $container2 = new class implements HasPriorityInterface, HasContainerLinkInterface
        {
            use WithContainerLinkTrait;

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
        $this->container->register($container);
        $this->container->get('don\'t know');
    }

    public function testUsingGlobalContainer()
    {
        $this->assertTrue(true);
        /*$this->expectException(NotFoundException::class);
        $container = new MyContainer();
        $this->container->register($container);
        $this->container->get('don\'t know');*/
    }

    public function testContainerMagicCall()
    {
        $this->container->register(new MyContainer());
        $service1 = $this->container->getMyService1();
        $this->assertInstanceOf(MyService1::class, $service1);
    }

    public function testContainerInvalidMagicCall()
    {
        $this->expectException(ContainerException::class);
        $this->container->invalid();
    }

    public function testOriginalKeyword()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->container->register(new class implements HasContainerLinkInterface
        {
            use WithContainerLinkTrait;

            public function getServiceOriginal(): int
            {
                return 1;
            }
        });
    }

    public function testSimpleDecoration()
    {
        $this->container->register(new class extends MyContainer
        {
            /**
             * @decorates MyService1
             */
            public function getMyService1Decorator(): MyService1
            {
                $container = $this->container->get('MyService1Original');
                return new class($container) extends MyService1
                {
                    private $decorates;

                    public function __construct(MyService1 $decorates)
                    {
                        $this->decorates = $decorates;
                    }

                    public function response(): string
                    {
                        return 'I\'m decorator! of ' . $this->decorates->response();
                    }
                };
            }
        });
        /** @var MyService1 $service */
        $service = $this->container->get(MyService1::class);
        $this->assertEquals('I\'m decorator! of I\'m service1', $service->response());
    }

    public function testAdvancedDecoration1()
    {
        $this->container->register(new MyContainer());
        $this->container->register(new class implements HasContainerLinkInterface
        {
            use WithContainerLinkTrait;

            /**
             * @decorates MyService1
             */
            public function getMyService1(): MyService1
            {
                return new class($this->container->get('MyService1Original')) extends MyService1
                {
                    private $decorates;

                    public function __construct(MyService1 $decorates)
                    {
                        $this->decorates = $decorates;
                    }

                    public function response(): string
                    {
                        return 'I\'m decorator! of ' . $this->decorates->response();
                    }
                };
            }
        });
        /** @var MyService1 $service */
        $service = $this->container->get(MyService1::class);
        $this->assertEquals('I\'m decorator! of I\'m service1', $service->response());
    }

    public function testAdvancedDecoration2()
    {
        $this->container->register(new class implements HasContainerLinkInterface
        {
            use WithContainerLinkTrait;

            /**
             * @decorates MyService1
             */
            public function getMyService1(): MyService1
            {
                return new class($this->container->get('MyService1Original')) extends MyService1
                {
                    private $decorates;

                    public function __construct(MyService1 $decorates)
                    {
                        $this->decorates = $decorates;
                    }

                    public function response(): string
                    {
                        return 'I\'m decorator! of ' . $this->decorates->response();
                    }
                };
            }
        });
        $this->container->register(new MyContainer());
        /** @var MyService1 $service */
        $service = $this->container->get(MyService1::class);
        $this->assertEquals('I\'m decorator! of I\'m service1', $service->response());
    }
}

interface MyServiceInterface
{
    public function response(): string;
}

class MyService1 implements MyServiceInterface
{
    private $enabled;

    public function __construct(bool $enable)
    {
        $this->enabled = $enable;
    }

    public function response(): string
    {
        return 'I\'m service1';
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

    public function response(): string
    {
        return 'I\'m service2';
    }
}

class MyContainer implements HasContainerLinkInterface
{
    use WithContainerLinkTrait;

    public function getMyService1(): MyService1
    {
        return new MyService1(true);
    }

    public function getMyService2(): MyService2
    {
        return new MyService2($this->container->get(MyService1::class));
    }
}
