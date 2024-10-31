<?php
class Twig_Node_Expression_Binary_And extends Twig_Node_Expression_Binary
{
    public function operator(Twig_Compiler $compiler)
    {
        return $compiler->raw('&&');
    }
}
class_alias('Twig_Node_Expression_Binary_And', 'Twig\Node\Expression\Binary\AndBinary', false);
