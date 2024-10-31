<?php
class Twig_TokenParser_Deprecated extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        return new Twig_Node_Deprecated($expr, $token->getLine(), $this->getTag());
    }
    public function getTag()
    {
        return 'deprecated';
    }
}
class_alias('Twig_TokenParser_Deprecated', 'Twig\TokenParser\DeprecatedTokenParser', false);
