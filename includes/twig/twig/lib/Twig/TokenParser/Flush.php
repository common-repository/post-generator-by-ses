<?php
class Twig_TokenParser_Flush extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        return new Twig_Node_Flush($token->getLine(), $this->getTag());
    }
    public function getTag()
    {
        return 'flush';
    }
}
class_alias('Twig_TokenParser_Flush', 'Twig\TokenParser\FlushTokenParser', false);
