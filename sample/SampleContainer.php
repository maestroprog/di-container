<?php

use Maestroprog\Container\HasContainerLinkInterface;
use Maestroprog\Container\WithContainerLinkTrait;

class SampleContainer implements HasContainerLinkInterface
{
   use WithContainerLinkTrait;

    public function getCachePath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'SampleCache';
    }

    public function getFileCache(): FileCache
    {
        return new FileCache($this->container->get('cachePath'));
    }

    public function getCache(): CacheInterface
    {
        return $this->getFileCache();
    }

    public function getSampleService(): TestNamespace\SampleService
    {
        return new TestNamespace\SampleService($this->container->get(CacheInterface::class));
    }

    public function getSampleServiceAutoWire(): string
    {
        return TestNamespace\SampleServiceUsingFileCache::class;
    }
}
