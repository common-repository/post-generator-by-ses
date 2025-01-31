<?php
class Twig_TokenParser_Import extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $macro = $this->parser->getExpressionParser()->parseExpression();
        $this->parser->getStream()->expect('as');
        $var = new Twig_Node_Expression_AssignName($this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue(), $token->getLine());
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $this->parser->addImportedSymbol('template', $var->getAttribute('name'));
        return new Twig_Node_Import($macro, $var, $token->getLine(), $this->getTag());
    }
    public function getTag()
    {
        return 'import';
    }
}
class_alias('Twig_TokenParser_Import', 'Twig\TokenParser\ImportTokenParser', false);
