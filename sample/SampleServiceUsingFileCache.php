<?php

namespace TestNamespace;

class SampleServiceUsingFileCache
{
    private $cache;

    public function __construct(\FileCache $cache)
    {
        $this->cache = $cache;
    }

    public function getSample1(): int
    {
        $result = $this->cache->get('result') ?? 0;
        $result++;
        $this->cache->set('result', $result);
        return $result;
    }
}
