<?php
class Twig_TokenParser_If extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideIfFork']);
        $tests = [$expr, $body];
        $else = null;
        $end = false;
        while (!$end) {
            switch ($stream->next()->getValue()) {
                case 'else':
                    $stream->expect(Twig_Token::BLOCK_END_TYPE);
                    $else = $this->parser->subparse([$this, 'decideIfEnd']);
                    break;
                case 'elseif':
                    $expr = $this->parser->getExpressionParser()->parseExpression();
                    $stream->expect(Twig_Token::BLOCK_END_TYPE);
                    $body = $this->parser->subparse([$this, 'decideIfFork']);
                    $tests[] = $expr;
                    $tests[] = $body;
                    break;
                case 'endif':
                    $end = true;
                    break;
                default:
                    throw new Twig_Error_Syntax(sprintf('Unexpected end of template. Twig was looking for the following tags "else", "elseif", or "endif" to close the "if" block started at line %d).', $lineno), $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        }
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        return new Twig_Node_If(new Twig_Node($tests), $else, $lineno, $this->getTag());
    }
    public function decideIfFork(Twig_Token $token)
    {
        return $token->test(['elseif', 'else', 'endif']);
    }
    public function decideIfEnd(Twig_Token $token)
    {
        return $token->test(['endif']);
    }
    public function getTag()
    {
        return 'if';
    }
}
class_alias('Twig_TokenParser_If', 'Twig\TokenParser\IfTokenParser', false);
