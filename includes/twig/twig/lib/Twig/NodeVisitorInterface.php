<?php
interface Twig_NodeVisitorInterface
{
    public function enterNode(Twig_NodeInterface $node, Twig_Environment $env);
    public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env);
    public function getPriority();
}
class_alias('Twig_NodeVisitorInterface', 'Twig\NodeVisitor\NodeVisitorInterface', false);
class_exists('Twig_Environment');
class_exists('Twig_Node');
