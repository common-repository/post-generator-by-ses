<?php
class Twig_TokenParser_Macro extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
        $arguments = $this->parser->getExpressionParser()->parseArguments(true, true);
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $this->parser->pushLocalScope();
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        if ($token = $stream->nextIf(Twig_Token::NAME_TYPE)) {
            $value = $token->getValue();
            if ($value != $name) {
                throw new Twig_Error_Syntax(sprintf('Expected endmacro for macro "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        }
        $this->parser->popLocalScope();
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $this->parser->setMacro($name, new Twig_Node_Macro($name, new Twig_Node_Body([$body]), $arguments, $lineno, $this->getTag()));
    }
    public function decideBlockEnd(Twig_Token $token)
    {
        return $token->test('endmacro');
    }
    public function getTag()
    {
        return 'macro';
    }
}
class_alias('Twig_TokenParser_Macro', 'Twig\TokenParser\MacroTokenParser', false);
