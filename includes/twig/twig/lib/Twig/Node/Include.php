<?php
class Twig_Node_Include extends Twig_Node implements Twig_NodeOutputInterface
{
    public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $variables = null, $only = false, $ignoreMissing = false, $lineno, $tag = null)
    {
        $nodes = ['expr' => $expr];
        if (null !== $variables) {
            $nodes['variables'] = $variables;
        }
        parent::__construct($nodes, ['only' => (bool) $only, 'ignore_missing' => (bool) $ignoreMissing], $lineno, $tag);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        if ($this->getAttribute('ignore_missing')) {
            $compiler
                ->write("try {\n")
                ->indent()
            ;
        }
        $this->addGetTemplate($compiler);
        $compiler->raw('->display(');
        $this->addTemplateArguments($compiler);
        $compiler->raw(");\n");
        if ($this->getAttribute('ignore_missing')) {
            $compiler
                ->outdent()
                ->write("} catch (Twig_Error_Loader \$e) {\n")
                ->indent()
                ->write("// ignore missing template\n")
                ->outdent()
                ->write("}\n\n")
            ;
        }
    }
    protected function addGetTemplate(Twig_Compiler $compiler)
    {
        $compiler
             ->write('$this->loadTemplate(')
             ->subcompile($this->getNode('expr'))
             ->raw(', ')
             ->repr($this->getTemplateName())
             ->raw(', ')
             ->repr($this->getTemplateLine())
             ->raw(')')
         ;
    }
    protected function addTemplateArguments(Twig_Compiler $compiler)
    {
        if (!$this->hasNode('variables')) {
            $compiler->raw(false === $this->getAttribute('only') ? '$context' : 'array()');
        } elseif (false === $this->getAttribute('only')) {
            $compiler
                ->raw('array_merge($context, ')
                ->subcompile($this->getNode('variables'))
                ->raw(')')
            ;
        } else {
            $compiler->subcompile($this->getNode('variables'));
        }
    }
}
class_alias('Twig_Node_Include', 'Twig\Node\IncludeNode', false);
