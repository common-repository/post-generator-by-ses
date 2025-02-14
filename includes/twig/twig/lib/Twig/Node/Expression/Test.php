<?php
class Twig_Node_Expression_Test extends Twig_Node_Expression_Call
{
    public function __construct(Twig_NodeInterface $node, $name, Twig_NodeInterface $arguments = null, $lineno)
    {
        $nodes = ['node' => $node];
        if (null !== $arguments) {
            $nodes['arguments'] = $arguments;
        }
        parent::__construct($nodes, ['name' => $name], $lineno);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $test = $compiler->getEnvironment()->getTest($name);
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'test');
        $this->setAttribute('thing', $test);
        if ($test instanceof Twig_SimpleTest) {
            $this->setAttribute('arguments', $test->getArguments());
        }
        if ($test instanceof Twig_TestCallableInterface || $test instanceof Twig_SimpleTest) {
            $this->setAttribute('callable', $test->getCallable());
        }
        if ($test instanceof Twig_SimpleTest) {
            $this->setAttribute('is_variadic', $test->isVariadic());
        }
        $this->compileCallable($compiler);
    }
}
class_alias('Twig_Node_Expression_Test', 'Twig\Node\Expression\TestExpression', false);
