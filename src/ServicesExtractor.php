<?php

namespace Maestroprog\Container;

class ServicesExtractor
{
    /**
     * @param $container
     *
     * @return array
     * @throws \ReflectionException
     */
    public function extractServicesId($container): array
    {
        $list = [];

        $reflection = new \ReflectionClass($container);

        foreach ($reflection->getMethods() as $method) {
            $methodName = $method->getName();
            if (substr($methodName, 0, 3) === 'get' && strlen($methodName) > 3) {
                $argument = $this->argumentInfoFrom($method);
                if ($argument->isInternal()) {
                    // не добавляем в общий контейнер внутренние аргументы
                    continue;
                }
                $serviceId = substr($methodName, 3);
                $list[$serviceId] = $argument;
            }
        }

        return $list;
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return Argument
     */
    private function argumentInfoFrom(\ReflectionMethod $method): Argument
    {
        static $modifiers = [
            'internal',
            'decorates',
            'private'
        ];
        $docs = explode("\n", $method->getDocComment() ?: '');
        array_walk($docs, function (&$key) {
            $key = trim($key, "* \t\r");
        });

        $result = [];
        foreach ($docs as $key) {

            if ('@' !== substr($key, 0, 1)) {
                continue;
            }
            [$modifier, $arguments] = explode(' ', ltrim($key, '@') . ' ', 2);

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

        return new Argument($method->getShortName(), (string)$method->getReturnType(), $result);
    }
}
