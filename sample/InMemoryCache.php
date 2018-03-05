<?php

final class InMemoryCache implements CacheInterface
{
    private $memory = [];

    public function get(string $key)
    {
        return $this->memory[$key] ?? null;
    }

    public function set(string $key, $data)
    {
        $this->memory[$key] = $data;
    }
}
