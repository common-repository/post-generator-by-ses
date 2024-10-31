<?php
class Twig_Node_Expression_AssignName extends Twig_Node_Expression_Name
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->raw('$context[')
            ->string($this->getAttribute('name'))
            ->raw(']')
        ;
    }
}
class_alias('Twig_Node_Expression_AssignName', 'Twig\Node\Expression\AssignNameExpression', false);
