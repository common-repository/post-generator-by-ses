<?php
class Twig_Node_Expression_Binary_LessEqual extends Twig_Node_Expression_Binary
{
    public function operator(Twig_Compiler $compiler)
    {
        return $compiler->raw('<=');
    }
}
class_alias('Twig_Node_Expression_Binary_LessEqual', 'Twig\Node\Expression\Binary\LessEqualBinary', false);
