<?php

namespace Maestroprog\Container;

use Psr\Container\ContainerInterface;

abstract class AbstractCompiledContainer
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get(string $serviceId)
    {
        if (substr_count($serviceId, '\\')) {
            if (method_exists($this, $method = 'get' . str_replace('\\', '_', $serviceId))) {
                return $this->{$method}();
            }
        } elseif (method_exists($this, $method = 'get' . $serviceId)) {
            return $this->{$method}();
        }
        return $this->container->get($serviceId);
    }
}
