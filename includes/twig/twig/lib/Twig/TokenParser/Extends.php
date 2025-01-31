<?php
class Twig_TokenParser_Extends extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        if (!$this->parser->isMainScope()) {
            throw new Twig_Error_Syntax('Cannot extend from a block.', $token->getLine(), $stream->getSourceContext());
        }
        if (null !== $this->parser->getParent()) {
            throw new Twig_Error_Syntax('Multiple extends tags are forbidden.', $token->getLine(), $stream->getSourceContext());
        }
        $this->parser->setParent($this->parser->getExpressionParser()->parseExpression());
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
    }
    public function getTag()
    {
        return 'extends';
    }
}
class_alias('Twig_TokenParser_Extends', 'Twig\TokenParser\ExtendsTokenParser', false);
