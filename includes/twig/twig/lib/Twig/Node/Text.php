<?php
class Twig_Node_Text extends Twig_Node implements Twig_NodeOutputInterface
{
    public function __construct($data, $lineno)
    {
        parent::__construct([], ['data' => $data], $lineno);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('echo ')
            ->string($this->getAttribute('data'))
            ->raw(";\n")
        ;
    }
}
class_alias('Twig_Node_Text', 'Twig\Node\TextNode', false);
