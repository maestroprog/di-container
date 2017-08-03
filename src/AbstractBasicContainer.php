<?php

namespace Qwerty\Container;

use Psr\Container\ContainerInterface;

abstract class AbstractBasicContainer implements IterableContainerInterface
{
    private $list;
    private $instances = [];

    /**
     * @var ContainerInterface
     */
    private $globalContainer;

    public function get($id)
    {
        if (class_exists($id)) {
            if (!($_id = array_search($id, $this->list))) {
                $id = (new \ReflectionClass($id))->getShortName();
            } else {
                $id = $_id;
            }
        }
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        if (isset($this->list[$id])) {
            $method = 'get' . $id;
            if (!method_exists($this, $method)) {
                throw new \LogicException('Call unknown method "' . $method . ' for getting service "' . $id . '".');
            }
            return $this->instances[$id] = $this->{$method}();
        }
        if (null === $this->globalContainer) {
            throw new \RuntimeException('Cannot find service "' . $id . '", global container not isset.');
        }
        return $this->globalContainer->get($id);
    }

    public function has($id)
    {
        return in_array($id, $this->list, true);
    }

    final public function list(): array
    {
        if (null === $this->list) {
            $this->list = [];
            $reflect = new \ReflectionClass($this);

            foreach ($reflect->getMethods() as $method) {
                $methodName = $method->getName();
                if (substr($methodName, 0, 3) === 'get' && strlen($methodName) > 3) {
                    $this->list[substr($methodName, 3)] = (string)$method->getReturnType();
                }
            }
        }
        return $this->list;
    }

    public function registered(ContainerInterface $container)
    {
        $this->globalContainer = $container;
    }
}
