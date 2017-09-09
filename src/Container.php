<?php

declare(strict_types=1);

namespace Maestroprog\Container;

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

    /**
     * @var Argument[]
     */
    private $list = [];
    private $priorities = [];

    /**
     * @return Container|AbstractCompiledContainer|\CompiledContainer
     */
    public static function instance(): Container
    {
        return self::$instance ?? self::$instance = new static();
    }

    private function __construct()
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

        $this->loadServices($id, $container);

        if ($container instanceof AbstractBasicContainer) {
            $container->registered($this); // todo kill this crutch
        }
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

    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) === 'get') {
            return $this->get(substr($name, 3));
        }
        throw new \RuntimeException('Unknown using magic method "' . $name . '".');
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
