<?php

use Qwerty\Container\AbstractBasicContainer;
use Qwerty\Container\HasPriorityInterface;

class SampleOverrideContainer extends AbstractBasicContainer implements HasPriorityInterface
{
    public function getCache(): CacheInterface
    {
        return new InMemoryCache();
    }

    public function getSampleService(): SampleService
    {
        return new SampleService($this->get(InMemoryCache::class));
    }

    public function getMyCustomSampleService(): SampleService
    {
        return new class extends SampleService
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
