<?php
@trigger_error('The Twig_Filter_Node class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFilter instead.', E_USER_DEPRECATED);
class Twig_Filter_Node extends Twig_Filter
{
    protected $class;
    public function __construct($class, array $options = [])
    {
        parent::__construct($options);
        $this->class = $class;
    }
    public function getClass()
    {
        return $this->class;
    }
    public function compile()
    {
    }
}
