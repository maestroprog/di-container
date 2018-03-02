<?php

use Maestroprog\Container\HasContainerLinkInterface;
use Maestroprog\Container\HasPriorityInterface;
use Maestroprog\Container\WithContainerLinkTrait;
use TestNamespace\SampleService;

class SampleOverrideContainer implements HasContainerLinkInterface, HasPriorityInterface
{
    use WithContainerLinkTrait;

    public function getCache(): CacheInterface
    {
        return new InMemoryCache();
    }

    /**
     * @decorates Cache
     * @return CacheInterface
     */
    public function getCacheDecorator(): CacheInterface
    {
        return new CacheDecorator($this->container->get('CacheOriginal'));
    }

    public function getSampleService(): SampleService
    {
        return new SampleService($this->container->get(CacheInterface::class));
    }

    public function getMyCustomSampleService(): SampleService
    {
        $cache = $this->container->get(CacheInterface::class);
        return new class($cache) extends SampleService
        {
            public function test()
            {

            }
        };
    }

    public function priority(): int
    {
        return 1;
    }
}
