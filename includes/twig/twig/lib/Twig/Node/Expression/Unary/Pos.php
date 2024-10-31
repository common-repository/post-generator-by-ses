<?php
class Twig_Node_Expression_Unary_Pos extends Twig_Node_Expression_Unary
{
    public function operator(Twig_Compiler $compiler)
    {
        $compiler->raw('+');
    }
}
class_alias('Twig_Node_Expression_Unary_Pos', 'Twig\Node\Expression\Unary\PosUnary', false);
