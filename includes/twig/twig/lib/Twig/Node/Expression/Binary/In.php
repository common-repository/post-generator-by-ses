<?php
class Twig_Node_Expression_Binary_In extends Twig_Node_Expression_Binary
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->raw('twig_in_filter(')
            ->subcompile($this->getNode('left'))
            ->raw(', ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }
    public function operator(Twig_Compiler $compiler)
    {
        return $compiler->raw('in');
    }
}
class_alias('Twig_Node_Expression_Binary_In', 'Twig\Node\Expression\Binary\InBinary', false);
