<?php
class Twig_Node_Expression_Binary_EndsWith extends Twig_Node_Expression_Binary
{
    public function compile(Twig_Compiler $compiler)
    {
        $left = $compiler->getVarName();
        $right = $compiler->getVarName();
        $compiler
            ->raw(sprintf('(is_string($%s = ', $left))
            ->subcompile($this->getNode('left'))
            ->raw(sprintf(') && is_string($%s = ', $right))
            ->subcompile($this->getNode('right'))
            ->raw(sprintf(') && (\'\' === $%2$s || $%2$s === substr($%1$s, -strlen($%2$s))))', $left, $right))
        ;
    }
    public function operator(Twig_Compiler $compiler)
    {
        return $compiler->raw('');
    }
}
class_alias('Twig_Node_Expression_Binary_EndsWith', 'Twig\Node\Expression\Binary\EndsWithBinary', false);
