<?php

declare(strict_types=1);

namespace Maestroprog\Container;

use Psr\Container\ContainerInterface;

interface IterableContainerInterface extends ContainerInterface
{
    /**
     * Вернёт список зарегистрированных в контейнере "id".
     *
     * @return Argument[]
     */
    public function list(): array;
}
