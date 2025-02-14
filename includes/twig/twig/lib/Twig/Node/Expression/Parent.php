<?php
class Twig_Node_Expression_Parent extends Twig_Node_Expression
{
    public function __construct($name, $lineno, $tag = null)
    {
        parent::__construct([], ['output' => false, 'name' => $name], $lineno, $tag);
    }
    public function compile(Twig_Compiler $compiler)
    {
        if ($this->getAttribute('output')) {
            $compiler
                ->addDebugInfo($this)
                ->write('$this->displayParentBlock(')
                ->string($this->getAttribute('name'))
                ->raw(", \$context, \$blocks);\n")
            ;
        } else {
            $compiler
                ->raw('$this->renderParentBlock(')
                ->string($this->getAttribute('name'))
                ->raw(', $context, $blocks)')
            ;
        }
    }
}
class_alias('Twig_Node_Expression_Parent', 'Twig\Node\Expression\ParentExpression', false);
