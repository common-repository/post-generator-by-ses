<?php
class Twig_Node_With extends Twig_Node
{
    public function __construct(Twig_Node $body, Twig_Node $variables = null, $only = false, $lineno, $tag = null)
    {
        $nodes = ['body' => $body];
        if (null !== $variables) {
            $nodes['variables'] = $variables;
        }
        parent::__construct($nodes, ['only' => (bool) $only], $lineno, $tag);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        if ($this->hasNode('variables')) {
            $varsName = $compiler->getVarName();
            $compiler
                ->write(sprintf('$%s = ', $varsName))
                ->subcompile($this->getNode('variables'))
                ->raw(";\n")
                ->write(sprintf("if (!is_array(\$%s)) {\n", $varsName))
                ->indent()
                ->write("throw new Twig_Error_Runtime('Variables passed to the \"with\" tag must be a hash.');\n")
                ->outdent()
                ->write("}\n")
            ;
            if ($this->getAttribute('only')) {
                $compiler->write("\$context = array('_parent' => \$context);\n");
            } else {
                $compiler->write("\$context['_parent'] = \$context;\n");
            }
            $compiler->write(sprintf("\$context = array_merge(\$context, \$%s);\n", $varsName));
        } else {
            $compiler->write("\$context['_parent'] = \$context;\n");
        }
        $compiler
            ->subcompile($this->getNode('body'))
            ->write("\$context = \$context['_parent'];\n")
        ;
    }
}
class_alias('Twig_Node_With', 'Twig\Node\WithNode', false);
