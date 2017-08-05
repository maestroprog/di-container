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
    private $instances = [];
    private $ids = [];
    private $types = [];
    private $map = [];
    private $list = [];
    private $priorites = [];

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

        $this->containers[++$id] = $container;

        $priority = 0;
        if ($container instanceof HasPriorityInterface) {
            $priority = $container->priority();
        }
        $this->priorites[$id] = $priority;

        $this->loadServices($id, $container);

        if ($container instanceof AbstractBasicContainer) {
            $container->registered($this); // todo kill this crutch
        }
    }

    protected function loadServices(int $containerId, IterableContainerInterface $container)
    {
        $list = $container->list();
        /*
        $intersect = array_intersect_assoc($list, $this->map);
        foreach ($intersect as $id => $type) {

        }*/

        // getting different services

        foreach ($list as $serviceId => $returnType) {
            $this->addService($containerId, $serviceId, $returnType);
        }
        /*// todo intersect
        if ($diff = array_diff_assoc($list, $this->map)) {
            foreach ($diff as $serviceId => $returnType) {
                $this->addService($containerId, $serviceId, $returnType);
            }
        }*/

//        $this->ids = array_merge($this->ids, array_keys($list));

    }

    protected function addService(int $containerId, string $serviceId, string $returnType)
    {
        if (array_key_exists($serviceId, $this->ids)) {
            if ($this->list[$serviceId] !== $returnType) {
                if (class_exists($returnType)) {
                    throw new \LogicException(sprintf(
                        'Invalid override: Service "%s" with type "%s" override service with type "%s"',
                        $serviceId,
                        $returnType,
                        $this->map[$serviceId]
                    ));
                } else {
                    throw new \LogicException(sprintf(
                        'Invalid override: Service "%s" with type "%s" override service with type "%s"',
                        $serviceId,
                        $returnType,
                        $this->map[$serviceId]
                    ));
                }
            }
            if ($this->priorites[$this->ids[$serviceId]] === $this->priorites[$containerId]) {
                throw new \LogicException('Equals priority in two services, please, fix it!');
            } elseif ($this->priorites[$this->ids[$serviceId]] > $this->priorites[$containerId]) {
                return;
            }
        }
        $this->types[$returnType] = $containerId;
        $this->ids[$serviceId] = $containerId;
        $this->ids[$returnType] = $containerId;
        if ($returnType !== $serviceId && class_exists($returnType)) {
        }

        $this->map[$returnType] = $serviceId; // todo check this reversed mapping check
        $this->list[$serviceId] = $returnType;
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
        return $this->instances[$id] = $this->containers[$this->ids[$id]]->get($id);
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        return array_key_exists($id, $this->ids);
    }

    /**
     * @inheritdoc
     */
    public function list(): array
    {
        return $this->list;
    }
}
