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
    private $ids = [];
    private $types = [];
    private $map = [];
    private $instances = [];

    private function __construct()
    {
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

        $list = $container->list();
        $intersect = array_intersect_key(array_flip($this->ids), $list);

        // todo intersect
        $diff = array_diff_assoc($list, $this->map);
        if ($diff) {
            $combined = $diff;
            $this->map = array_merge($this->map, $combined);
        }
        var_dump($this->map);

        $this->containers[$id++] = $container;

        $this->ids = array_merge($this->ids, array_keys($list));

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
