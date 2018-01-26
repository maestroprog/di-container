<?php

declare(strict_types=1);

namespace Maestroprog\Container;

use Psr\Container\ContainerInterface;

class Container implements IterableContainerInterface
{
    /**
     * @var ContainerInterface[]
     */
    private $containers;
    private $instances = [];
    private $ids = [];
    private $types = [];
    private $map = [];

    /**
     * @var Argument[]
     */
    private $list = [];
    private $priorities = [];

    public function __construct()
    {
        $this->containers = [];
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
        $this->priorities[$id] = $priority;

        if ($container instanceof IterableContainerInterface) {
            $this->loadServices($id, $container);
        }
    }


    public function aget($id)
    {
        $id = ucfirst($id);
        if (class_exists($id) || interface_exists($id) || trait_exists($id)) {
            if ($serviceId = array_search($id, $this->list)) {
                $id = $serviceId;
            }
        }
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        if ($serviceId = array_search($id, $this->original)) {
            $id = $serviceId;
        }
        $method = 'get' . $id;
        if (isset($this->list[$id]) || method_exists($this, $method)) {
            return $this->instances[$id] = $this->{$method}();
        }
        if (null === $this->globalContainer) {
            throw new \RuntimeException('Cannot find service "' . $id . '", global container not isset.');
        }
        return $this->globalContainer->get($id);
    }

    protected function registerContainer($container): void
    {
        $this->list = [];
        $excluded = [];

        $reflect = new \ReflectionClass($container);

        foreach ($reflect->getMethods() as $method) {
            $methodName = $method->getName();
            if (substr($methodName, 0, 3) === 'get' && strlen($methodName) > 3) {
                $argument = $this->argumentInfoFrom($method);
                if ($argument->isInternal()) {
                    // не добавляем в общий контейнер внутренние аргументы
                    continue;
                }
                $serviceId = substr($methodName, 3);
                if (!in_array($serviceId, $excluded, true)) {
                    $this->list[$serviceId] = $argument;
                }
                if (!$argument->isDecorator()) {
                    $this->original[$serviceId] = $serviceId . 'Original';
                } else {
                    $decorates = $argument->getDecoratorArguments();
                    if ($serviceId !== $decorates) {
                        $excluded[] = $decorates;
                        unset($this->list[$decorates]);
                    }
                }
            }
        }
    }

    /**
     * @param \ReflectionMethod $method
     * @return Argument
     */
    private function argumentInfoFrom(\ReflectionMethod $method): Argument
    {
        static $modifiers = [
            'internal',
            'decorates',
            'private'
        ];
        $docs = explode("\n", $method->getDocComment());
        array_walk($docs, function (&$key) {
            $key = trim($key, "* \t\r");
        });

        $result = [];
        foreach ($docs as $key) {

            if ('@' !== substr($key, 0, 1)) {
                continue;
            }
            list($modifier, $arguments) = explode(' ', ltrim($key, '@') . ' ', 2);

            if (in_array($modifier, $modifiers, true)) {

                if (isset($result[$modifier])) {
                    throw new \LogicException(sprintf(
                        'Modifier "%s" of service "%s" cannot be duplicated.',
                        $modifier,
                        substr($method->getShortName(), 3)
                    ));
                }

                $result[$modifier] = trim($arguments);
            }
        }

        return new Argument(
            (string)$method->getReturnType(),
            $result
        );
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
        if ($notFound = !$this->has($id)) {
            if (!class_exists($id)) {
                throw new NotFoundException('Not found "' . $id . '" in Di container.');
            }
            $instance = $id;
        } else {
            $instance = $this->containers[$this->ids[$id]]->get($id);
        }
        if (is_string($instance) && class_exists($instance)) {
            $class = new \ReflectionClass($instance);
            $constructor = $class->getConstructor();
            $parameters = $constructor->getParameters();
            $arguments = [];
            foreach ($parameters as $parameter) {
                $argType = $parameter->getClass()->getName();
                $arguments[] = $this->get($argType);
            }
            $instance = new $instance(...$arguments);
        } elseif ($notFound) {
            throw new NotFoundException('Not found "' . $id . '" in Di container.');
        }
        return $this->instances[$id] = $instance;
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

    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) === 'get') {
            return $this->get(substr($name, 3));
        }
        throw new \RuntimeException('Unknown using magic method "' . $name . '".');
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
     * @param IterableContainerInterface $container
     * @return void
     */
    protected function loadServices(int $containerId, IterableContainerInterface $container)
    {
        $list = $container->list();

        foreach ($list as $serviceId => $argument) {
            $this->addService($containerId, $serviceId, $argument);
        }
    }

    /**
     * @param int $containerId
     * @param string $serviceId
     * @param Argument $argument
     * @return void
     */
    protected function addService(int $containerId, string $serviceId, Argument $argument)
    {
        if (substr($serviceId, -8) === 'Original') {
            throw new \LogicException(sprintf(
                'Service id cannot ends with "Original" keyword in "%s".',
                $serviceId
            ));
        }
        $returnType = $argument->getReturnType();

        if (array_key_exists($serviceId, $this->ids)) {

            $isSubclass = is_a($this->list[$serviceId]->getReturnType(), $returnType, true);

            if ($this->list[$serviceId]->getReturnType() !== $returnType && !$isSubclass) {
                throw new \LogicException(sprintf(
                    'Invalid override: Service "%s" with type "%s" override service with type "%s"',
                    $serviceId,
                    $returnType,
                    $this->list[$serviceId]->getReturnType()
                ));
            }

            if ($argument->isDecorator() && $serviceId === $argument->getDecoratorArguments()) {

                $decoratesId = $serviceId;
                if ($this->has($decoratesId)) {
                    $decoratesArgument = $this->list[$decoratesId];
                    $decoratesContainer = $this->ids[$decoratesId];

                    $this->remove($decoratesArgument, $decoratesId);

                    $this->write($decoratesArgument, $decoratesId . 'Original', $decoratesContainer);
                }

            } elseif ($this->list[$serviceId]->isDecorator()) {
                $serviceId .= 'Original';

                $this->ids[$serviceId] = $containerId;
                $this->list[$serviceId] = $argument;

                return;

            } elseif ($this->priorities[$this->ids[$serviceId]] === $this->priorities[$containerId]) {
                throw new \LogicException('Equals priority in two services, please, fix it!');

            } elseif ($this->priorities[$this->ids[$serviceId]] > $this->priorities[$containerId]) {
                return;
            }
        }

        if ($argument->isDecorator()) {
            $decoratesId = $argument->getDecoratorArguments();
            $serviceId = $decoratesId;
        }

        $this->write($argument, $serviceId, $containerId);
    }

    /**
     * @param Argument $argument
     * @param string $serviceId
     * @param int $containerId
     * @return void
     */
    protected function write(Argument $argument, string $serviceId, int $containerId)
    {
        $returnType = $argument->getReturnType();

        $this->types[$returnType] = $containerId; // todo check two or more containers with once returnType
        $this->ids[$serviceId] = $containerId;
        $this->ids[$returnType] = $containerId; // todo check (see up)
        /*if ($returnType !== $serviceId && class_exists($returnType)) {
        }*/

        $this->map[$returnType] = $serviceId; // todo check this reversed mapping check
        $this->list[$serviceId] = $argument;
    }

    /**
     * @param Argument $argument
     * @param string $serviceId
     * @return void
     */
    protected function remove(Argument $argument, string $serviceId)
    {
        $returnType = $argument->getReturnType();

        // todo check this unset
        unset(
            $this->ids[$serviceId],
            $this->list[$serviceId],
            $this->types[$returnType],
            $this->ids[$returnType],
            $this->map[$returnType]
        );
    }
}
