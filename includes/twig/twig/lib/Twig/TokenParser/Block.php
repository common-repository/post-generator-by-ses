<?php
class Twig_TokenParser_Block extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
        if ($this->parser->hasBlock($name)) {
            throw new Twig_Error_Syntax(sprintf("The block '%s' has already been defined line %d.", $name, $this->parser->getBlock($name)->getTemplateLine()), $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }
        $this->parser->setBlock($name, $block = new Twig_Node_Block($name, new Twig_Node([]), $lineno));
        $this->parser->pushLocalScope();
        $this->parser->pushBlockStack($name);
        if ($stream->nextIf(Twig_Token::BLOCK_END_TYPE)) {
            $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
            if ($token = $stream->nextIf(Twig_Token::NAME_TYPE)) {
                $value = $token->getValue();
                if ($value != $name) {
                    throw new Twig_Error_Syntax(sprintf('Expected endblock for block "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
                }
            }
        } else {
            $body = new Twig_Node([
                new Twig_Node_Print($this->parser->getExpressionParser()->parseExpression(), $lineno),
            ]);
        }
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $block->setNode('body', $body);
        $this->parser->popBlockStack();
        $this->parser->popLocalScope();
        return new Twig_Node_BlockReference($name, $lineno, $this->getTag());
    }
    public function decideBlockEnd(Twig_Token $token)
    {
        return $token->test('endblock');
    }
    public function getTag()
    {
        return 'block';
    }
}
class_alias('Twig_TokenParser_Block', 'Twig\TokenParser\BlockTokenParser', false);
