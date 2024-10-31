<?php
interface Twig_CompilerInterface
{
    public function compile(Twig_NodeInterface $node);
    public function getSource();
}
