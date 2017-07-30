<?php

namespace Qwerty\Container;

abstract class AbstractBasicContainer implements IterableContainerInterface
{
    private $list;
    private $instances = [];

    public function get($id)
    {
        $id = (new \ReflectionClass($id))->getShortName();
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        $method = 'get' . $id;
        return $this->instances[$id] = $this->{$method}();
    }

    public function has($id)
    {
        return in_array($id, $this->list, true);
    }

    public function list(): array
    {
        if (null === $this->list) {
            $this->list = [];
            $reflect = new \ReflectionClass($this);

            foreach ($reflect->getMethods() as $method) {
                $methodName = $method->getName();
                if (substr($methodName, 0, 3) === 'get' && strlen($methodName) > 3) {
                    $this->list[] = substr($methodName, 3);
                }
            }
        }
        return $this->list;
    }
}
