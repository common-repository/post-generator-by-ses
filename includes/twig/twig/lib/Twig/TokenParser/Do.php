<?php
class Twig_TokenParser_Do extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        return new Twig_Node_Do($expr, $token->getLine(), $this->getTag());
    }
    public function getTag()
    {
        return 'do';
    }
}
class_alias('Twig_TokenParser_Do', 'Twig\TokenParser\DoTokenParser', false);
