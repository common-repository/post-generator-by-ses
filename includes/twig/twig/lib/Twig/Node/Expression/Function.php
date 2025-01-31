<?php
class Twig_Node_Expression_Function extends Twig_Node_Expression_Call
{
    public function __construct($name, Twig_NodeInterface $arguments, $lineno)
    {
        parent::__construct(['arguments' => $arguments], ['name' => $name, 'is_defined_test' => false], $lineno);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $function = $compiler->getEnvironment()->getFunction($name);
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'function');
        $this->setAttribute('thing', $function);
        $this->setAttribute('needs_environment', $function->needsEnvironment());
        $this->setAttribute('needs_context', $function->needsContext());
        $this->setAttribute('arguments', $function->getArguments());
        if ($function instanceof Twig_FunctionCallableInterface || $function instanceof Twig_SimpleFunction) {
            $callable = $function->getCallable();
            if ('constant' === $name && $this->getAttribute('is_defined_test')) {
                $callable = 'twig_constant_is_defined';
            }
            $this->setAttribute('callable', $callable);
        }
        if ($function instanceof Twig_SimpleFunction) {
            $this->setAttribute('is_variadic', $function->isVariadic());
        }
        $this->compileCallable($compiler);
    }
}
class_alias('Twig_Node_Expression_Function', 'Twig\Node\Expression\FunctionExpression', false);
