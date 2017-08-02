<?php

use Qwerty\Container\AbstractCompiledContainer;

final class CompiledContainer extends AbstractCompiledContainer
{
    public function getFileCache(): FileCache
    {
        // return $this->get('FileCache');
    }

    public function getSampleService(): SampleService
    {
        // return $this->get('SampleService');
    }

    public function getCache(): Cache
    {
        // return $this->get('Cache');
    }

    public function getSampleService(): SampleService
    {
        // return $this->get('SampleService');
    }

    public function getMyCustomSampleService(): MyCustomSampleService
    {
        // return $this->get('MyCustomSampleService');
    }
}