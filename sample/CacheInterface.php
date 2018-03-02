<?php

interface CacheInterface
{
    public function get(string $key);

    public function set(string $key, $data);
}
