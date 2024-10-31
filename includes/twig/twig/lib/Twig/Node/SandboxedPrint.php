<?php
class Twig_Node_SandboxedPrint extends Twig_Node_Print
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('echo $this->env->getExtension(\'Twig_Extension_Sandbox\')->ensureToStringAllowed(')
            ->subcompile($this->getNode('expr'))
            ->raw(");\n")
        ;
    }
    protected function removeNodeFilter(Twig_Node $node)
    {
        if ($node instanceof Twig_Node_Expression_Filter) {
            return $this->removeNodeFilter($node->getNode('node'));
        }
        return $node;
    }
}
class_alias('Twig_Node_SandboxedPrint', 'Twig\Node\SandboxedPrintNode', false);
