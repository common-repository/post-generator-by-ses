<?php
class Twig_Node_Expression_Unary_Neg extends Twig_Node_Expression_Unary
{
    public function operator(Twig_Compiler $compiler)
    {
        $compiler->raw('-');
    }
}
class_alias('Twig_Node_Expression_Unary_Neg', 'Twig\Node\Expression\Unary\NegUnary', false);
