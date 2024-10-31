<?php
interface Twig_NodeInterface extends Countable, IteratorAggregate
{
    public function compile(Twig_Compiler $compiler);
    public function getLine();
    public function getNodeTag();
}
