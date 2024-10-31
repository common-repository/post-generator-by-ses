<?php
class Twig_Node_Expression_Binary_FloorDiv extends Twig_Node_Expression_Binary
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->raw('(int) floor(');
        parent::compile($compiler);
        $compiler->raw(')');
    }
    public function operator(Twig_Compiler $compiler)
    {
        return $compiler->raw('/');
    }
}
class_alias('Twig_Node_Expression_Binary_FloorDiv', 'Twig\Node\Expression\Binary\FloorDivBinary', false);
