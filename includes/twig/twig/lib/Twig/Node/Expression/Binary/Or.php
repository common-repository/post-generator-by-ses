<?php
class Twig_Node_Expression_Binary_Or extends Twig_Node_Expression_Binary
{
    public function operator(Twig_Compiler $compiler)
    {
        return $compiler->raw('||');
    }
}
class_alias('Twig_Node_Expression_Binary_Or', 'Twig\Node\Expression\Binary\OrBinary', false);
