<?php
class Twig_Node_Expression_Binary_NotIn extends Twig_Node_Expression_Binary
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->raw('!twig_in_filter(')
            ->subcompile($this->getNode('left'))
            ->raw(', ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }
    public function operator(Twig_Compiler $compiler)
    {
        return $compiler->raw('not in');
    }
}
class_alias('Twig_Node_Expression_Binary_NotIn', 'Twig\Node\Expression\Binary\NotInBinary', false);
