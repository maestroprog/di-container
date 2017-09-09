<?php

class CacheDecorator implements CacheInterface
{
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $key)
    {
        echo 'Getting ' . $key . PHP_EOL;
        return $this->cache->get($key);
    }

    public function set(string $key, $data)
    {
        echo 'Setting ' . $key . PHP_EOL;
        return $this->cache->set($key, $data);
    }
}
