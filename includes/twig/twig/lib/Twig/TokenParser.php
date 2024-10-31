<?php
abstract class Twig_TokenParser implements Twig_TokenParserInterface
{
    protected $parser;
    public function setParser(Twig_Parser $parser)
    {
        $this->parser = $parser;
    }
}
class_alias('Twig_TokenParser', 'Twig\TokenParser\AbstractTokenParser', false);
