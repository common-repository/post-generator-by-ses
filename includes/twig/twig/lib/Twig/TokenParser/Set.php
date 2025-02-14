<?php
class Twig_TokenParser_Set extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $names = $this->parser->getExpressionParser()->parseAssignmentExpression();
        $capture = false;
        if ($stream->nextIf(Twig_Token::OPERATOR_TYPE, '=')) {
            $values = $this->parser->getExpressionParser()->parseMultitargetExpression();
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
            if (count($names) !== count($values)) {
                throw new Twig_Error_Syntax('When using set, you must have the same number of variables and assignments.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        } else {
            $capture = true;
            if (count($names) > 1) {
                throw new Twig_Error_Syntax('When using set with a block, you cannot have a multi-target.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
            $values = $this->parser->subparse([$this, 'decideBlockEnd'], true);
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
        }
        return new Twig_Node_Set($capture, $names, $values, $lineno, $this->getTag());
    }
    public function decideBlockEnd(Twig_Token $token)
    {
        return $token->test('endset');
    }
    public function getTag()
    {
        return 'set';
    }
}
class_alias('Twig_TokenParser_Set', 'Twig\TokenParser\SetTokenParser', false);
