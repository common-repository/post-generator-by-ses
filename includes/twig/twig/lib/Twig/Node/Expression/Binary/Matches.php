<?php
class Twig_Node_Expression_Binary_Matches extends Twig_Node_Expression_Binary
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->raw('preg_match(')
            ->subcompile($this->getNode('right'))
            ->raw(', ')
            ->subcompile($this->getNode('left'))
            ->raw(')')
        ;
    }
    public function operator(Twig_Compiler $compiler)
    {
        return $compiler->raw('');
    }
}
class_alias('Twig_Node_Expression_Binary_Matches', 'Twig\Node\Expression\Binary\MatchesBinary', false);
