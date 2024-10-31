<?php
@trigger_error('The Twig_Test class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleTest instead.', E_USER_DEPRECATED);
abstract class Twig_Test implements Twig_TestInterface, Twig_TestCallableInterface
{
    protected $options;
    protected $arguments = [];
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'callable' => null,
        ], $options);
    }
    public function getCallable()
    {
        return $this->options['callable'];
    }
}
