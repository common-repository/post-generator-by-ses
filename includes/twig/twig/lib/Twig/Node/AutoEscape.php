<?php
class Twig_Node_AutoEscape extends Twig_Node
{
    public function __construct($value, Twig_NodeInterface $body, $lineno, $tag = 'autoescape')
    {
        parent::__construct(['body' => $body], ['value' => $value], $lineno, $tag);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('body'));
    }
}
class_alias('Twig_Node_AutoEscape', 'Twig\Node\AutoEscapeNode', false);
