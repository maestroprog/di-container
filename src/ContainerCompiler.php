<?php

namespace Qwerty\Container;

class ContainerCompiler
{
    private $container;

    public function __construct(IterableContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function compile(string $path)
    {
        $class = <<<PHP
<?php

use Qwerty\Container\AbstractCompiledContainer;

final class CompiledContainer extends AbstractCompiledContainer
{
PHP;
        foreach ($this->container->list() as $id) {

            $name = str_replace('\\', '', $id);
            $class .= <<<PHP

    public function get{$name}(): {$id}
    {
        // return \$this->get('$id');
    }

PHP;

        }

        $class .= <<<PHP
}
PHP;

        file_put_contents($path, $class);

    }
}