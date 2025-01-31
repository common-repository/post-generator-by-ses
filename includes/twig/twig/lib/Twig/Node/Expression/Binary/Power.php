<?php
class Twig_Node_Expression_Binary_Power extends Twig_Node_Expression_Binary
{
    public function compile(Twig_Compiler $compiler)
    {
        if (PHP_VERSION_ID >= 50600) {
            return parent::compile($compiler);
        }
        $compiler
            ->raw('pow(')
            ->subcompile($this->getNode('left'))
            ->raw(', ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }
    public function operator(Twig_Compiler $compiler)
    {
        return $compiler->raw('**');
    }
}
class_alias('Twig_Node_Expression_Binary_Power', 'Twig\Node\Expression\Binary\PowerBinary', false);
