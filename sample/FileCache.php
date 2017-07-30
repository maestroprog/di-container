<?php

final class FileCache implements CacheInterface
{
    private $file;
    private $memory;

    public function __construct(string $path)
    {
        $this->file = $path;
        if (file_exists($path)) {
            $this->memory = unserialize(file_get_contents($path));
            if (!$this->memory) {
                $this->memory = [];
            }
        }
    }

    public function get(string $key)
    {
        return $this->memory[$key] ?? null;
    }

    public function set(string $key, $data)
    {
        $this->memory[$key] = $data;
    }

    public function __destruct()
    {
        $this->write();
    }

    private function write()
    {
        var_dump($this->file);
        file_put_contents($this->file, serialize($this->memory));
    }
}
