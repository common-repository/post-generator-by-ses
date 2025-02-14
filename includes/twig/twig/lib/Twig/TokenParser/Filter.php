<?php
class Twig_TokenParser_Filter extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $name = $this->parser->getVarName();
        $ref = new Twig_Node_Expression_BlockReference(new Twig_Node_Expression_Constant($name, $token->getLine()), null, $token->getLine(), $this->getTag());
        $filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref, $this->getTag());
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $block = new Twig_Node_Block($name, $body, $token->getLine());
        $this->parser->setBlock($name, $block);
        return new Twig_Node_Print($filter, $token->getLine(), $this->getTag());
    }
    public function decideBlockEnd(Twig_Token $token)
    {
        return $token->test('endfilter');
    }
    public function getTag()
    {
        return 'filter';
    }
}
class_alias('Twig_TokenParser_Filter', 'Twig\TokenParser\FilterTokenParser', false);
