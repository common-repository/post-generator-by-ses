<?php
interface Twig_LexerInterface
{
    public function tokenize($code, $name = null);
}
