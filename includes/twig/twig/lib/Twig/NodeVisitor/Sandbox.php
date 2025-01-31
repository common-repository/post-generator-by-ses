<?php
class Twig_NodeVisitor_Sandbox extends Twig_BaseNodeVisitor
{
    protected $inAModule = false;
    protected $tags;
    protected $filters;
    protected $functions;
    protected function doEnterNode(Twig_Node $node, Twig_Environment $env)
    {
        if ($node instanceof Twig_Node_Module) {
            $this->inAModule = true;
            $this->tags = [];
            $this->filters = [];
            $this->functions = [];
            return $node;
        } elseif ($this->inAModule) {
            if ($node->getNodeTag() && !isset($this->tags[$node->getNodeTag()])) {
                $this->tags[$node->getNodeTag()] = $node;
            }
            if ($node instanceof Twig_Node_Expression_Filter && !isset($this->filters[$node->getNode('filter')->getAttribute('value')])) {
                $this->filters[$node->getNode('filter')->getAttribute('value')] = $node;
            }
            if ($node instanceof Twig_Node_Expression_Function && !isset($this->functions[$node->getAttribute('name')])) {
                $this->functions[$node->getAttribute('name')] = $node;
            }
            if ($node instanceof Twig_Node_Expression_Binary_Range && !isset($this->functions['range'])) {
                $this->functions['range'] = $node;
            }
            if ($node instanceof Twig_Node_Print) {
                return new Twig_Node_SandboxedPrint($node->getNode('expr'), $node->getTemplateLine(), $node->getNodeTag());
            }
        }
        return $node;
    }
    protected function doLeaveNode(Twig_Node $node, Twig_Environment $env)
    {
        if ($node instanceof Twig_Node_Module) {
            $this->inAModule = false;
            $node->setNode('display_start', new Twig_Node([new Twig_Node_CheckSecurity($this->filters, $this->tags, $this->functions), $node->getNode('display_start')]));
        }
        return $node;
    }
    public function getPriority()
    {
        return 0;
    }
}
class_alias('Twig_NodeVisitor_Sandbox', 'Twig\NodeVisitor\SandboxNodeVisitor', false);
