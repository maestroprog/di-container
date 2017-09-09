<?php

declare(strict_types=1);

namespace Maestroprog\Container\Tests {

    use PHPUnit\Framework\TestCase;
    use Maestroprog\Container\AbstractBasicContainer;
    use Maestroprog\Container\AbstractCompiledContainer;
    use Maestroprog\Container\Container;
    use Maestroprog\Container\ContainerCompiler;
    use Psr\Container\NotFoundExceptionInterface;

    class ContainerCompilerTest extends TestCase
    {
        public function testCompile()
        {
            $container = clone Container::instance();
            $container->register(new MyContainer2());
            $compiler = new ContainerCompiler($container);
            $compiler->compile($php = tempnam(sys_get_temp_dir(), 'compiler'));
            $this->assertFileExists($php);
            require_once $php;

            $this->assertInstanceOf(AbstractCompiledContainer::class, $container = new \CompiledContainer($container));
            try {
                (new \ReflectionClass(\CompiledContainer::class))->getMethod('getMyServiceOff');
            } catch (\ReflectionException $e) {
                $this->assertTrue(false, 'Method getMyServiceOff does not exists.');
            }
            $myService1 = $container->get(MyService1::class);
            $this->assertInstanceOf(MyService1::class, $myService1);
            $myService2 = $container->get('MyServiceOff');
            $this->assertInstanceOf(MyService1::class, $myService2);
            $this->assertTrue($myService1 === $myService2);
            try {
                $container->get('InvalidUnknown');
            } catch (NotFoundExceptionInterface $e) {
                ;
            }
            $this->assertNotNull($e);
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

        public function getGlobalService(): \MyService
        {
            return new \MyService();
        }
    }
}

namespace {
    class MyService
    {

    }
}
