<?php

declare(strict_types=1);

namespace Maestroprog\Container;

use Psr\Container\ContainerInterface;

trait WithContainerLinkTrait
{
    /** @var ContainerInterface */
    protected $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}
