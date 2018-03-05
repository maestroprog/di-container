<?php

declare(strict_types=1);

namespace Maestroprog\Container;

use Psr\Container\ContainerInterface;

class Container implements IterableContainerInterface
{
    protected $servicesExtractor;

    /**
     * Маппинг идентификаторов сервисов на контейнеры.
     *
     * @var array serviceId => containerId
     */
    private $ids = [];
    /**
     * Маппинг типов сервисов на их идентификаторы.
     *
     * @var array returnType => serviceId[]
     */
    private $map = [];
    /**
     * Маппинг декорируемых сервисов на декораторы.
     *
     * @var array serviceId => decoratorId
     */
    private $decorators = [];
    /**
     * Свойства сервисов.
     *
     * @var Argument[] serviceId => properties
     */
    private $list = [];
    /**
     * Маппинг переопределяемых сервисов на переопределяющие.
     * Отладочная информация.
     *
     * @var array fromId => [fromContainerId, toId, toContainerId]
     */
    private $overridden = [];

    /**
     * @var ContainerInterface[]
     */
    protected $containers = [];

    /**
     * @var int[] priorities of containers
     */
    protected $priorities = [];

    /**
     * @var array of services instances
     */
    protected $instances = [];

    private $state = [];

    public function __construct()
    {
        $this->servicesExtractor = new ServicesExtractor();
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        $id = ucfirst($id);
        if (array_key_exists($id, $this->instances)) {
            // fast getter
            return $this->instances[$id];
        }
        $found = $this->has($id);
        if ($found) {
            if (!isset($this->ids[$id])) {
                if (count($this->map[$id]) > 1) {
                    $msg = 'An attempt to get an indefinite service. Please use the explicit service identifier.';
                    throw new ContainerException($msg);
                }
                $id = end($this->map[$id]);
            }
        } elseif (!class_exists($id)) {
            throw new NotFoundException('Not found "' . $id . '" in DI container.');
        } else {
            $instance = $id;
        }
        if (array_key_exists($id, $this->instances)) {
            // slow-fast getter
            return $this->instances[$id];
        }

        if (in_array($id, $this->state)) {
            throw new ContainerException(sprintf(
                'Recursive get detected: "%s > %s".',
                implode(' => ', $this->state),
                $id
            ));
        }

        try {
            $this->state[] = $id;

            if ($found) {
                if ($this->containers[$this->ids[$id]] instanceof IterableContainerInterface) {
                    $instance = $this->containers[$this->ids[$id]]->get($id);
                } else {
                    $method = $this->list[$id]->getMethodName();
                    $container = $this->containers[$this->ids[$id]];
                    if (method_exists($container, $method)) {
                        $instance = $this->instances[$id] = $container->{$method}();
                    } else {
                        throw new NotFoundException('Cannot find service "' . $id . '".');
                    }
                }
            } elseif (!isset($instance)) {
                throw new \LogicException('$instance must be set.');
            }

            if (is_string($instance) && class_exists($instance)) {
                $class = new \ReflectionClass($instance);
                $constructor = $class->getConstructor();
                $parameters = $constructor->getParameters();
                $arguments = [];
                foreach ($parameters as $parameter) {
                    if ($parameter->getClass()) {
                        $argType = $parameter->getClass()->getName();
                    } else {
                        $argType = $parameter->getType()->getName();
                    }
                    $arguments[] = $this->get($argType);
                }
                $instance = new $instance(...$arguments);
            }

            return $this->instances[$id] = $instance;
        } finally {
            if (end($this->state) === $id) {
                array_pop($this->state);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function has($id): bool
    {
        return array_key_exists($id, $this->ids) || array_key_exists($id, $this->map);
    }

    /**
     * @inheritdoc
     */
    public function list(): array
    {
        return $this->list;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed|string
     * @throws ContainerException
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) === 'get') {
            return $this->get(substr($name, 3));
        }
        throw new ContainerException('Unknown using magic method "' . $name . '".');
    }

    /**
     * Регистрирует новый контейнер.
     *
     * @param IterableContainerInterface|HasContainerLinkInterface|object $container
     *
     * @return void
     */
    public function register($container): void
    {
        static $id = 0;

        $this->containers[++$id] = $container;

        $priority = 0;
        if ($container instanceof HasPriorityInterface) {
            $priority = $container->priority();
        }
        $this->priorities[$id] = $priority;

        if ($container instanceof IterableContainerInterface) {
            $servicesList = $container->list();
        } else {
            try {
                $servicesList = $this->servicesExtractor->extractServicesId($container);
            } catch (\ReflectionException $e) {
                $servicesList = [];
            }
        }
        $this->loadServices($id, $servicesList);
        if ($container instanceof HasContainerLinkInterface) {
            $container->setContainer($this);
        }
    }

    public function addDependencies(array $dependencies): self
    {
        foreach ($dependencies as $dependency) {
            $this->addDependency($dependency);
        }

        return $this;
    }

    public function addDependency($dependency, string $alias = null): void
    {
        if (!is_object($dependency)) {
            throw new \InvalidArgumentException('Invalid dependency type.');
        }
        $this->instances[get_class($dependency)] = $dependency;
        if (null !== $alias) {
            $this->instances[$alias] = $dependency;
        }
    }

    /**
     * @param int $containerId
     * @param Argument[] $list
     *
     * @return void
     */
    protected function loadServices(int $containerId, array $list): void
    {
        foreach ($list as $serviceId => $argument) {
            $this->addService($containerId, $serviceId, $argument);
        }
    }

    /**
     * @param int $containerId
     * @param string $serviceId
     * @param Argument $argument
     *
     * @return void
     */
    protected function addService(int $containerId, string $serviceId, Argument $argument): void
    {
        if (substr($serviceId, -8) === 'Original') {
            throw new \InvalidArgumentException(sprintf(
                'Service id cannot ends with "Original" keyword in "%s".',
                $serviceId
            ));
        }

        if (array_key_exists($serviceId, $this->ids)) {
            // overriding

            $returnType = $argument->getReturnType();

            $otherReturnType = $this->list[$serviceId]->getReturnType();
            $isSubclass = is_a($otherReturnType, $returnType, true);

            if ($otherReturnType !== $returnType && !$isSubclass) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid override: Service "%s" with type "%s" override service with type "%s"',
                    $serviceId,
                    $returnType,
                    $otherReturnType
                ));
            }

            if ($argument->isDecorator() && $serviceId === $argument->getDecoratedService()) {
            } elseif ($this->list[$serviceId]->isDecorator()) {
            } elseif ($this->priorities[$this->ids[$serviceId]] === $this->priorities[$containerId]) {
                throw new \InvalidArgumentException('Equals priority in two services, please, fix it!');

            } elseif ($this->priorities[$this->ids[$serviceId]] > $this->priorities[$containerId]) {
                return;
            }
        }

        $this->append($argument, $serviceId, $containerId);
    }

    /**
     * @param Argument $argument
     * @param string $serviceId
     * @param int $containerId
     *
     * @return void
     */
    protected function append(Argument $argument, string $serviceId, int $containerId): void
    {
        $returnType = $argument->getReturnType();

        $overridden = false;
        if (isset($this->decorators[$serviceId])) {
            if ($this->list[$serviceId]->isDecorator() && $argument->isDecorator()) {
                throw new \InvalidArgumentException('Cannot add two or more decorators for one service.');
            }
            $decoratorId = $this->decorators[$serviceId];
            $this->overridden[$serviceId] = [$containerId, $decoratorId, $this->ids[$serviceId]];
            $serviceId .= 'Original';
            $overridden = true;
        }

        if ($argument->isDecorator()) {
            $decoratedService = $argument->getDecoratedService();
            if (isset($this->list[$decoratedService])) {
                $this->overridden[$decoratedService] = [$this->ids[$decoratedService], $serviceId, $containerId];
                $this->replace($decoratedService, $decoratedService . 'Original');
            }
            $this->decorators[$decoratedService] = $serviceId;
            $serviceId = $decoratedService;
        }

        $this->list[$serviceId] = $argument;
        $this->ids[$serviceId] = $containerId;
        if (!$overridden) {
            $this->map[$returnType][] = $serviceId;
        }
    }

    protected function replace($fromId, $toId): void
    {
        $this->list[$toId] = $this->list[$fromId];
        $this->ids[$toId] = $this->ids[$fromId];
        $returnType = $this->list[$toId]->getReturnType();
        $mapIndex = array_search($fromId, $this->map[$returnType], true);
        unset($this->map[$returnType][$mapIndex]);
        unset($this->ids[$fromId]);
        unset($this->list[$fromId]);
    }

    /**
     * @param Argument $argument
     * @param string $serviceId
     *
     * @return void
     */
    protected function remove(Argument $argument, string $serviceId): void
    {
        $returnType = $argument->getReturnType();

        unset($this->map[$returnType][array_search($serviceId, $this->map[$returnType], true)]);
        if (empty($this->map[$returnType])) {
            unset($this->map[$returnType]);
        }

        unset($this->list[$serviceId], $this->ids[$serviceId]);
    }
}
