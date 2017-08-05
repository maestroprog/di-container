<?php

namespace Maestroprog\Container;

use Psr\Container\ContainerInterface;

interface IterableContainerInterface extends ContainerInterface
{
    /**
     * Вернёт список зарегистрированных в контейнере "id".
     *
     * @return string[]
     */
    public function list(): array;
}
