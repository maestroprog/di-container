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
}