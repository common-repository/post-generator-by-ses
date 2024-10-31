<?php
class Twig_TokenParser_With extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $variables = null;
        $only = false;
        if (!$stream->test(Twig_Token::BLOCK_END_TYPE)) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
            $only = $stream->nextIf(Twig_Token::NAME_TYPE, 'only');
        }
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideWithEnd'], true);
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        return new Twig_Node_With($body, $variables, $only, $token->getLine(), $this->getTag());
    }
    public function decideWithEnd(Twig_Token $token)
    {
        return $token->test('endwith');
    }
    public function getTag()
    {
        return 'with';
    }
}
class_alias('Twig_TokenParser_With', 'Twig\TokenParser\WithTokenParser', false);
