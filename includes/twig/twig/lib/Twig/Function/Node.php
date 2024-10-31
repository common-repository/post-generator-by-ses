<?php
@trigger_error('The Twig_Function_Node class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFunction instead.', E_USER_DEPRECATED);
class Twig_Function_Node extends Twig_Function
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
