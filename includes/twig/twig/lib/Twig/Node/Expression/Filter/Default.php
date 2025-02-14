<?php
class Twig_Node_Expression_Filter_Default extends Twig_Node_Expression_Filter
{
    public function __construct(Twig_NodeInterface $node, Twig_Node_Expression_Constant $filterName, Twig_NodeInterface $arguments, $lineno, $tag = null)
    {
        $default = new Twig_Node_Expression_Filter($node, new Twig_Node_Expression_Constant('default', $node->getTemplateLine()), $arguments, $node->getTemplateLine());
        if ('default' === $filterName->getAttribute('value') && ($node instanceof Twig_Node_Expression_Name || $node instanceof Twig_Node_Expression_GetAttr)) {
            $test = new Twig_Node_Expression_Test_Defined(clone $node, 'defined', new Twig_Node(), $node->getTemplateLine());
            $false = count($arguments) ? $arguments->getNode(0) : new Twig_Node_Expression_Constant('', $node->getTemplateLine());
            $node = new Twig_Node_Expression_Conditional($test, $default, $false, $node->getTemplateLine());
        } else {
            $node = $default;
        }
        parent::__construct($node, $filterName, $arguments, $lineno, $tag);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
class_alias('Twig_Node_Expression_Filter_Default', 'Twig\Node\Expression\Filter\DefaultFilter', false);
