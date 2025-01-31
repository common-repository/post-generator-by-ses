<?php
abstract class Twig_Node_Expression_Unary extends Twig_Node_Expression
{
    public function __construct(Twig_NodeInterface $node, $lineno)
    {
        parent::__construct(['node' => $node], [], $lineno);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->raw(' ');
        $this->operator($compiler);
        $compiler->subcompile($this->getNode('node'));
    }
    abstract public function operator(Twig_Compiler $compiler);
}
class_alias('Twig_Node_Expression_Unary', 'Twig\Node\Expression\Unary\AbstractUnary', false);
