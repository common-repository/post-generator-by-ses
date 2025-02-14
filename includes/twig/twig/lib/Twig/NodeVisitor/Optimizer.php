<?php
class Twig_NodeVisitor_Optimizer extends Twig_BaseNodeVisitor
{
    const OPTIMIZE_ALL = -1;
    const OPTIMIZE_NONE = 0;
    const OPTIMIZE_FOR = 2;
    const OPTIMIZE_RAW_FILTER = 4;
    const OPTIMIZE_VAR_ACCESS = 8;
    protected $loops = [];
    protected $loopsTargets = [];
    protected $optimizers;
    protected $prependedNodes = [];
    protected $inABody = false;
    public function __construct($optimizers = -1)
    {
        if (!is_int($optimizers) || $optimizers > (self::OPTIMIZE_FOR | self::OPTIMIZE_RAW_FILTER | self::OPTIMIZE_VAR_ACCESS)) {
            throw new InvalidArgumentException(sprintf('Optimizer mode "%s" is not valid.', $optimizers));
        }
        $this->optimizers = $optimizers;
    }
    protected function doEnterNode(Twig_Node $node, Twig_Environment $env)
    {
        if (self::OPTIMIZE_FOR === (self::OPTIMIZE_FOR & $this->optimizers)) {
            $this->enterOptimizeFor($node, $env);
        }
        if (PHP_VERSION_ID < 50400 && self::OPTIMIZE_VAR_ACCESS === (self::OPTIMIZE_VAR_ACCESS & $this->optimizers) && !$env->isStrictVariables() && !$env->hasExtension('Twig_Extension_Sandbox')) {
            if ($this->inABody) {
                if (!$node instanceof Twig_Node_Expression) {
                    if ('Twig_Node' !== get_class($node)) {
                        array_unshift($this->prependedNodes, []);
                    }
                } else {
                    $node = $this->optimizeVariables($node, $env);
                }
            } elseif ($node instanceof Twig_Node_Body) {
                $this->inABody = true;
            }
        }
        return $node;
    }
    protected function doLeaveNode(Twig_Node $node, Twig_Environment $env)
    {
        $expression = $node instanceof Twig_Node_Expression;
        if (self::OPTIMIZE_FOR === (self::OPTIMIZE_FOR & $this->optimizers)) {
            $this->leaveOptimizeFor($node, $env);
        }
        if (self::OPTIMIZE_RAW_FILTER === (self::OPTIMIZE_RAW_FILTER & $this->optimizers)) {
            $node = $this->optimizeRawFilter($node, $env);
        }
        $node = $this->optimizePrintNode($node, $env);
        if (self::OPTIMIZE_VAR_ACCESS === (self::OPTIMIZE_VAR_ACCESS & $this->optimizers) && !$env->isStrictVariables() && !$env->hasExtension('Twig_Extension_Sandbox')) {
            if ($node instanceof Twig_Node_Body) {
                $this->inABody = false;
            } elseif ($this->inABody) {
                if (!$expression && 'Twig_Node' !== get_class($node) && $prependedNodes = array_shift($this->prependedNodes)) {
                    $nodes = [];
                    foreach (array_unique($prependedNodes) as $name) {
                        $nodes[] = new Twig_Node_SetTemp($name, $node->getTemplateLine());
                    }
                    $nodes[] = $node;
                    $node = new Twig_Node($nodes);
                }
            }
        }
        return $node;
    }
    protected function optimizeVariables(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if ('Twig_Node_Expression_Name' === get_class($node) && $node->isSimple()) {
            $this->prependedNodes[0][] = $node->getAttribute('name');
            return new Twig_Node_Expression_TempName($node->getAttribute('name'), $node->getTemplateLine());
        }
        return $node;
    }
    protected function optimizePrintNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if (!$node instanceof Twig_Node_Print) {
            return $node;
        }
        $exprNode = $node->getNode('expr');
        if (
            $exprNode instanceof Twig_Node_Expression_BlockReference ||
            $exprNode instanceof Twig_Node_Expression_Parent
        ) {
            $exprNode->setAttribute('output', true);
            return $exprNode;
        }
        return $node;
    }
    protected function optimizeRawFilter(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if ($node instanceof Twig_Node_Expression_Filter && 'raw' == $node->getNode('filter')->getAttribute('value')) {
            return $node->getNode('node');
        }
        return $node;
    }
    protected function enterOptimizeFor(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if ($node instanceof Twig_Node_For) {
            $node->setAttribute('with_loop', false);
            array_unshift($this->loops, $node);
            array_unshift($this->loopsTargets, $node->getNode('value_target')->getAttribute('name'));
            array_unshift($this->loopsTargets, $node->getNode('key_target')->getAttribute('name'));
        } elseif (!$this->loops) {
            return;
        } elseif ($node instanceof Twig_Node_Expression_Name && 'loop' === $node->getAttribute('name')) {
            $node->setAttribute('always_defined', true);
            $this->addLoopToCurrent();
        } elseif ($node instanceof Twig_Node_Expression_Name && in_array($node->getAttribute('name'), $this->loopsTargets)) {
            $node->setAttribute('always_defined', true);
        } elseif ($node instanceof Twig_Node_BlockReference || $node instanceof Twig_Node_Expression_BlockReference) {
            $this->addLoopToCurrent();
        } elseif ($node instanceof Twig_Node_Include && !$node->getAttribute('only')) {
            $this->addLoopToAll();
        } elseif ($node instanceof Twig_Node_Expression_Function
            && 'include' === $node->getAttribute('name')
            && (
                !$node->getNode('arguments')->hasNode('with_context')
                 || false !== $node->getNode('arguments')->getNode('with_context')->getAttribute('value')
               )
        ) {
            $this->addLoopToAll();
        } elseif ($node instanceof Twig_Node_Expression_GetAttr
            && (
                !$node->getNode('attribute') instanceof Twig_Node_Expression_Constant
                || 'parent' === $node->getNode('attribute')->getAttribute('value')
               )
            && (
                true === $this->loops[0]->getAttribute('with_loop')
                || (
                    $node->getNode('node') instanceof Twig_Node_Expression_Name
                    && 'loop' === $node->getNode('node')->getAttribute('name')
                   )
               )
        ) {
            $this->addLoopToAll();
        }
    }
    protected function leaveOptimizeFor(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if ($node instanceof Twig_Node_For) {
            array_shift($this->loops);
            array_shift($this->loopsTargets);
            array_shift($this->loopsTargets);
        }
    }
    protected function addLoopToCurrent()
    {
        $this->loops[0]->setAttribute('with_loop', true);
    }
    protected function addLoopToAll()
    {
        foreach ($this->loops as $loop) {
            $loop->setAttribute('with_loop', true);
        }
    }
    public function getPriority()
    {
        return 255;
    }
}
class_alias('Twig_NodeVisitor_Optimizer', 'Twig\NodeVisitor\OptimizerNodeVisitor', false);
