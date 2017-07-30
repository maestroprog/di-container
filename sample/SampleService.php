<?php

class SampleService
{
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getSample1(): int
    {
        $time = $this->cache->get('time') ?? time();
        $result = $this->cache->get('result') ?? 0;
        for ($i = $time; $i <= time(); $i++) {
            $result += $i;
        }
        $this->cache->set('time', $i);
        $this->cache->set('result', $result);
        return $result;
    }
}
