<?php

namespace Qwerty\Container;

interface HasPriorityInterface
{
    public function getPriority(): int;
}
