<?php
class Twig_Node_Expression_Binary_Range extends Twig_Node_Expression_Binary
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->raw('range(')
            ->subcompile($this->getNode('left'))
            ->raw(', ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }
    public function operator(Twig_Compiler $compiler)
    {
        return $compiler->raw('..');
    }
}
class_alias('Twig_Node_Expression_Binary_Range', 'Twig\Node\Expression\Binary\RangeBinary', false);
