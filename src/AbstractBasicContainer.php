<?php

namespace Maestroprog\Container;

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
        $id = ucfirst($id);
        if (class_exists($id) || interface_exists($id) || trait_exists($id)) {
            if ($serviceId = array_search($id, $this->list)) {
                $id = $serviceId;
            }
        }
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        $method = 'get' . $id;
        if (isset($this->list[$id]) || method_exists($this, $method)) {
            return $this->instances[$id] = $this->{$method}();
        }
        if (null === $this->globalContainer) {
            throw new \RuntimeException('Cannot find service "' . $id . '", global container not isset.');
        }
        return $this->globalContainer->get($id);
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
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
