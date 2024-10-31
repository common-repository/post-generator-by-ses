<?php
@trigger_error('The Twig_Function_Function class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFunction instead.', E_USER_DEPRECATED);
class Twig_Function_Function extends Twig_Function
{
    protected $function;
    public function __construct($function, array $options = [])
    {
        $options['callable'] = $function;
        parent::__construct($options);
        $this->function = $function;
    }
    public function compile()
    {
        return $this->function;
    }
}
