<?php
class Twig_TokenParser_Include extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        list($variables, $only, $ignoreMissing) = $this->parseArguments();
        return new Twig_Node_Include($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }
    protected function parseArguments()
    {
        $stream = $this->parser->getStream();
        $ignoreMissing = false;
        if ($stream->nextIf(Twig_Token::NAME_TYPE, 'ignore')) {
            $stream->expect(Twig_Token::NAME_TYPE, 'missing');
            $ignoreMissing = true;
        }
        $variables = null;
        if ($stream->nextIf(Twig_Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }
        $only = false;
        if ($stream->nextIf(Twig_Token::NAME_TYPE, 'only')) {
            $only = true;
        }
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        return [$variables, $only, $ignoreMissing];
    }
    public function getTag()
    {
        return 'include';
    }
}
class_alias('Twig_TokenParser_Include', 'Twig\TokenParser\IncludeTokenParser', false);
