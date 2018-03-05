<?php

declare(strict_types=1);

namespace Maestroprog\Container;

use Psr\Container\ContainerInterface;

interface HasContainerLinkInterface
{
    public function setContainer(ContainerInterface $container): void;
}
