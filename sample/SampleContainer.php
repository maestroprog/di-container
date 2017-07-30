<?php

use Qwerty\Container\AbstractBasicContainer;

class SampleContainer extends AbstractBasicContainer
{
    public function getFileCache(): CacheInterface
    {
        return new FileCache(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'SampleCache');
    }

    public function getSampleService(): SampleService
    {
        return new SampleService($this->get(FileCache::class));
    }
}
