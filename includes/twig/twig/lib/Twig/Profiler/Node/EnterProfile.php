<?php
class Twig_Profiler_Node_EnterProfile extends Twig_Node
{
    public function __construct($extensionName, $type, $name, $varName)
    {
        parent::__construct([], ['extension_name' => $extensionName, 'name' => $name, 'type' => $type, 'var_name' => $varName]);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->write(sprintf('$%s = $this->env->getExtension(', $this->getAttribute('var_name')))
            ->repr($this->getAttribute('extension_name'))
            ->raw(");\n")
            ->write(sprintf('$%s->enter($%s = new Twig_Profiler_Profile($this->getTemplateName(), ', $this->getAttribute('var_name'), $this->getAttribute('var_name') . '_prof'))
            ->repr($this->getAttribute('type'))
            ->raw(', ')
            ->repr($this->getAttribute('name'))
            ->raw("));\n\n")
        ;
    }
}
class_alias('Twig_Profiler_Node_EnterProfile', 'Twig\Profiler\Node\EnterProfileNode', false);
