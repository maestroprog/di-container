<?php

declare(strict_types=1);

namespace Qwerty\Container;

use Psr\Container\ContainerInterface;

class Container implements IterableContainerInterface
{
    private static $instance;

    /**
     * @var ContainerInterface[]
     */
    private $containers;
    private $ids;
    private $map = [];
    private $instances = [];

    private function __construct()
    {
        $this->ids = [];
    }

    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) === 'get') {
            return $this->get(substr($name, 3));
        }
        throw new \RuntimeException('Unknown using magic method "' . $name . '".');
    }

    /**
     * @return Container|AbstractCompiledContainer|\CompiledContainer
     */
    public static function instance(): Container
    {
        return self::$instance ?? self::$instance = new static();
    }

    public static function boot()
    {

    }

    /**
     * Регистрирует новый контейнер.
     *
     * @param IterableContainerInterface $container
     * @return void
     */
    public function register(IterableContainerInterface $container)
    {
        static $id = 0;

        $ids = array_flip($container->list());
        $intersect = array_intersect_key($this->map, $ids);
        // todo intersect
        $diff = array_diff_key($ids, $this->map);
        if ($diff) {
            $combined = array_combine(array_flip($diff), array_fill(0, count($diff), $id));
            $this->map = array_merge($this->map, $combined);
        }

        $this->containers[$id++] = $container;
        $this->ids = array_merge($this->ids, array_flip($diff));
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        if (array_key_exists($id, $this->instances)) {
            // fast getter
            return $this->instances[$id];
        }
        if (!$this->has($id)) {
            throw new NotFoundException('Not found "' . $id . '" in Di container.');
        }
        return $this->instances[$id] = $this->containers[$this->map[$id]]->get($id);
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        return in_array($id, $this->ids, true);
    }

    /**
     * @inheritdoc
     */
    public function list(): array
    {
        return $this->ids;
    }
}
