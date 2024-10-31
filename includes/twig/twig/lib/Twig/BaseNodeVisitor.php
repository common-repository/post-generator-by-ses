<?php
abstract class Twig_BaseNodeVisitor implements Twig_NodeVisitorInterface
{
    final public function enterNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if (!$node instanceof Twig_Node) {
            throw new LogicException('Twig_BaseNodeVisitor only supports Twig_Node instances.');
        }
        return $this->doEnterNode($node, $env);
    }
    final public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if (!$node instanceof Twig_Node) {
            throw new LogicException('Twig_BaseNodeVisitor only supports Twig_Node instances.');
        }
        return $this->doLeaveNode($node, $env);
    }
    abstract protected function doEnterNode(Twig_Node $node, Twig_Environment $env);
    abstract protected function doLeaveNode(Twig_Node $node, Twig_Environment $env);
}
class_alias('Twig_BaseNodeVisitor', 'Twig\NodeVisitor\AbstractNodeVisitor', false);
class_exists('Twig_Environment');
class_exists('Twig_Node');
