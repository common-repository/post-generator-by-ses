<?php
class Twig_Node_Expression_NullCoalesce extends Twig_Node_Expression_Conditional
{
    public function __construct(Twig_NodeInterface $left, Twig_NodeInterface $right, $lineno)
    {
        $test = new Twig_Node_Expression_Binary_And(
            new Twig_Node_Expression_Test_Defined(clone $left, 'defined', new Twig_Node(), $left->getTemplateLine()),
            new Twig_Node_Expression_Unary_Not(new Twig_Node_Expression_Test_Null($left, 'null', new Twig_Node(), $left->getTemplateLine()), $left->getTemplateLine()),
            $left->getTemplateLine()
        );
        parent::__construct($test, $left, $right, $lineno);
    }
    public function compile(Twig_Compiler $compiler)
    {
        if (PHP_VERSION_ID >= 70000 && $this->getNode('expr2') instanceof Twig_Node_Expression_Name) {
            $this->getNode('expr2')->setAttribute('always_defined', true);
            $compiler
                ->raw('((')
                ->subcompile($this->getNode('expr2'))
                ->raw(') ?? (')
                ->subcompile($this->getNode('expr3'))
                ->raw('))')
            ;
        } else {
            parent::compile($compiler);
        }
    }
}
class_alias('Twig_Node_Expression_NullCoalesce', 'Twig\Node\Expression\NullCoalesceExpression', false);
