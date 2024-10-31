<?php
class Twig_Profiler_Node_LeaveProfile extends Twig_Node
{
    public function __construct($varName)
    {
        parent::__construct([], ['var_name' => $varName]);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->write("\n")
            ->write(sprintf("\$%s->leave(\$%s);\n\n", $this->getAttribute('var_name'), $this->getAttribute('var_name') . '_prof'))
        ;
    }
}
class_alias('Twig_Profiler_Node_LeaveProfile', 'Twig\Profiler\Node\LeaveProfileNode', false);
