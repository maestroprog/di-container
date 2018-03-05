<?php

declare(strict_types=1);

namespace Maestroprog\Container;

interface HasPriorityInterface
{
    public function priority(): int;
}
