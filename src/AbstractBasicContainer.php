<?php

namespace Maestroprog\Container;

use Psr\Container\ContainerInterface;

abstract class  AbstractBasicContainer implements IterableContainerInterface
{
    private $list;
    private $instances = [];

    /**
     * @var ContainerInterface
     */
    private $globalContainer;

    public function get($id)
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
        $method = 'get' . $id;
        if (isset($this->list[$id]) || method_exists($this, $method)) {
            return $this->instances[$id] = $this->{$method}();
        }
        if (null === $this->globalContainer) {
            throw new \RuntimeException('Cannot find service "' . $id . '", global container not isset.');
        }
        return $this->globalContainer->get($id);
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function has($id)
    {
        return in_array($id, $this->list, true);
    }

    final public function list(): array
    {
        if (null === $this->list) {
            $this->list = [];
            $reflect = new \ReflectionClass($this);

            foreach ($reflect->getMethods() as $method) {
                $methodName = $method->getName();
                if (substr($methodName, 0, 3) === 'get' && strlen($methodName) > 3) {
                    $argument = $this->argumentInfoFrom($method);
                    if ($argument->isInternal()) {
                        // не добавляем в общий контейнер внутренние аргументы
                        continue;
                    }
                    $this->list[substr($methodName, 3)] = $argument;
                }
            }
        }
        return $this->list;
    }

    public function registered(ContainerInterface $container)
    {
        $this->globalContainer = $container;
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
            $this->body($method->getFileName(), $method->getStartLine(), $method->getEndLine()),
            $result
        );
    }

    private function body(string $fileName, int $startLine, int $endLine): string
    {
        $file = file_get_contents($fileName);
        $lines = explode("\n", $file);
        $body = implode("\n", array_slice($lines, $startLine, $endLine - $startLine));
        return substr($body, $start = strpos($body, '{') + 1, strrpos($body, '}') - $start);
    }
}
