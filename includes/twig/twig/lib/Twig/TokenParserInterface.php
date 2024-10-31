<?php
interface Twig_TokenParserInterface
{
    public function setParser(Twig_Parser $parser);
    public function parse(Twig_Token $token);
    public function getTag();
}
class_alias('Twig_TokenParserInterface', 'Twig\TokenParser\TokenParserInterface', false);
class_exists('Twig_Parser');
class_exists('Twig_Token');
