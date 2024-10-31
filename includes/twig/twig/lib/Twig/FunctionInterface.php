<?php
interface Twig_FunctionInterface
{
    public function compile();
    public function needsEnvironment();
    public function needsContext();
    public function getSafe(Twig_Node $filterArgs);
    public function setArguments($arguments);
    public function getArguments();
}
