<?php

use Maestroprog\Container\AbstractBasicContainer;

class SampleContainer extends AbstractBasicContainer
{
    public function getCachePath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'SampleCache';
    }

    public function getFileCache(): CacheInterface
    {
        return new FileCache($this->get('cachePath'));
    }

    public function getSampleService(): SampleService
    {
        return new SampleService($this->get(FileCache::class));
    }
}
