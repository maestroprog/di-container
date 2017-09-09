<?php

namespace Maestroprog\Container;

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
        $scalar = ['integer', 'string', 'float', 'double', 'void', 'array'];
        $uses = [];

        $classBody = <<<PHP
final class CompiledContainer extends AbstractCompiledContainer
{
PHP;
        foreach ($this->container->list() as $id => $argument) {
            $returnType = $argument->getReturnType();

            if (!in_array($returnType, $scalar, true)) {

                $inGlobalNamespace = !substr_count($returnType, '\\');
                $shortType = array_reverse(explode('\\', $returnType))[0];

                if (!isset($uses[$returnType]) && $shortType !== $id) {
                    $classBody .= <<<PHP

    public function get{$shortType}(): {$returnType}
    {
        return \$this->get{$id}();
    }

PHP;
                }
                if (!isset($uses[$returnType]) && !$inGlobalNamespace) {
                    $fullType = str_replace('\\', '_', $returnType);
                    $classBody .= <<<PHP

    public function get{$fullType}(): {$returnType}
    {
        return \$this->get{$id}();
    }

PHP;
                }
                if (!$inGlobalNamespace) {
                    $uses[$returnType] = "\nuse {$returnType};";
                } else {
                    $uses[$returnType] = '';
                }
            }

            $classBody .= <<<PHP

    public function get{$id}(): {$returnType}
    {{$argument->getBody()}}

PHP;
        }

        $classBody .= <<<PHP
}

PHP;
        $uses = implode('', $uses);

        $class = <<<CLASS
<?php

use \Maestroprog\Container\AbstractCompiledContainer;

{$uses}

{$classBody}
CLASS;

        file_put_contents($path, $class);
    }
}
