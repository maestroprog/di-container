<?php

use Maestroprog\Container\AbstractBasicContainer;

class SampleContainer extends AbstractBasicContainer
{
    public function getCachePath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'SampleCache';
    }

    public function getFileCache(): FileCache
    {
        return new FileCache($this->get('cachePath'));
    }

    public function getCache(): CacheInterface
    {
        return $this->getFileCache();
    }

    public function getSampleService(): TestNamespace\SampleService
    {
        return new TestNamespace\SampleService($this->get(CacheInterface::class));
    }

    public function getSampleServiceAutoWire(): string
    {
        return TestNamespace\SampleServiceUsingFileCache::class;
    }
}
