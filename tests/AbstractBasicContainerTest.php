<?php

namespace Maestroprog\Container\Tests;

use Maestroprog\Container\AbstractBasicContainer;
use PHPUnit\Framework\TestCase;

class AbstractBasicContainerTest extends TestCase
{
    public function testContainerInternalService()
    {
        $container = new class extends AbstractBasicContainer
        {
            /**
             * @internal
             * @return int
             */
            public function getInternalService()
            {
                return 1;
            }
        };
        $this->assertEquals(1, $container->get('InternalService'));
        $this->assertEmpty($container->list());
    }

    public function testContainerDuplicatedModifier()
    {
        $this->expectException(\LogicException::class);
        $container = new class extends AbstractBasicContainer
        {
            /**
             * @internal
             * @internal
             * @return int
             */
            public function getInternalService()
            {
                return 1;
            }
        };
        $this->assertEmpty($container->list());
    }
}
