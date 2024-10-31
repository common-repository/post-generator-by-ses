<?php
interface Twig_TokenParserBrokerInterface
{
    public function getTokenParser($tag);
    public function setParser(Twig_ParserInterface $parser);
    public function getParser();
}
