<?php
@trigger_error('The Twig_Function class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFunction instead.', E_USER_DEPRECATED);
abstract class Twig_Function implements Twig_FunctionInterface, Twig_FunctionCallableInterface
{
    protected $options;
    protected $arguments = [];
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'needs_environment' => false,
            'needs_context' => false,
            'callable' => null,
        ], $options);
    }
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }
    public function getArguments()
    {
        return $this->arguments;
    }
    public function needsEnvironment()
    {
        return $this->options['needs_environment'];
    }
    public function needsContext()
    {
        return $this->options['needs_context'];
    }
    public function getSafe(Twig_Node $functionArgs)
    {
        if (isset($this->options['is_safe'])) {
            return $this->options['is_safe'];
        }
        if (isset($this->options['is_safe_callback'])) {
            return call_user_func($this->options['is_safe_callback'], $functionArgs);
        }
        return [];
    }
    public function getCallable()
    {
        return $this->options['callable'];
    }
}
