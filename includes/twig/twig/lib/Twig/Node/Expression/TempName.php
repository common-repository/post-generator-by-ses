<?php
class Twig_Node_Expression_TempName extends Twig_Node_Expression
{
    public function __construct($name, $lineno)
    {
        parent::__construct([], ['name' => $name], $lineno);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->raw('$_')
            ->raw($this->getAttribute('name'))
            ->raw('_')
        ;
    }
}
class_alias('Twig_Node_Expression_TempName', 'Twig\Node\Expression\TempNameExpression', false);
