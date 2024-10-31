<?php
class Twig_TokenParser_Spaceless extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideSpacelessEnd'], true);
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        return new Twig_Node_Spaceless($body, $lineno, $this->getTag());
    }
    public function decideSpacelessEnd(Twig_Token $token)
    {
        return $token->test('endspaceless');
    }
    public function getTag()
    {
        return 'spaceless';
    }
}
class_alias('Twig_TokenParser_Spaceless', 'Twig\TokenParser\SpacelessTokenParser', false);
