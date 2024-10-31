<?php
@trigger_error('The Twig_Filter_Function class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFilter instead.', E_USER_DEPRECATED);
class Twig_Filter_Function extends Twig_Filter
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
