<?php
class Twig_Extension_Optimizer extends Twig_Extension
{
    protected $optimizers;
    public function __construct($optimizers = -1)
    {
        $this->optimizers = $optimizers;
    }
    public function getNodeVisitors()
    {
        return [new Twig_NodeVisitor_Optimizer($this->optimizers)];
    }
    public function getName()
    {
        return 'optimizer';
    }
}
class_alias('Twig_Extension_Optimizer', 'Twig\Extension\OptimizerExtension', false);
