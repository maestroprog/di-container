<?php

use Qwerty\Container\AbstractCompiledContainer;

final class CompiledContainer extends AbstractCompiledContainer
{
    public function getCacheInterface(): CacheInterface
    {
        // return $this->get('CacheInterface');
    }

    public function getSampleService(): SampleService
    {
        // return $this->get('SampleService');
    }

    public function getCacheInterface(): CacheInterface
    {
        // return $this->get('CacheInterface');
    }

    public function getSampleService(): SampleService
    {
        // return $this->get('SampleService');
    }
}