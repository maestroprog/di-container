<?php

namespace Qwerty\Container;

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
        return $this->container->get($serviceId);
    }
}
