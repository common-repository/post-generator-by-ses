<?php
class Twig_Node_SetTemp extends Twig_Node
{
    public function __construct($name, $lineno)
    {
        parent::__construct([], ['name' => $name], $lineno);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $compiler
            ->addDebugInfo($this)
            ->write('if (isset($context[')
            ->string($name)
            ->raw('])) { $_')
            ->raw($name)
            ->raw('_ = $context[')
            ->repr($name)
            ->raw(']; } else { $_')
            ->raw($name)
            ->raw("_ = null; }\n")
        ;
    }
}
class_alias('Twig_Node_SetTemp', 'Twig\Node\SetTempNode', false);
