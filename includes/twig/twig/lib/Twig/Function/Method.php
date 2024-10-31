<?php
@trigger_error('The Twig_Function_Method class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFunction instead.', E_USER_DEPRECATED);
class Twig_Function_Method extends Twig_Function
{
    protected $extension;
    protected $method;
    public function __construct(Twig_ExtensionInterface $extension, $method, array $options = [])
    {
        $options['callable'] = [$extension, $method];
        parent::__construct($options);
        $this->extension = $extension;
        $this->method = $method;
    }
    public function compile()
    {
        return sprintf('$this->env->getExtension(\'%s\')->%s', get_class($this->extension), $this->method);
    }
}
