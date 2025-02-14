<?php
class Twig_TokenParser_Embed extends Twig_TokenParser_Include
{
    public function parse(Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $parent = $this->parser->getExpressionParser()->parseExpression();
        list($variables, $only, $ignoreMissing) = $this->parseArguments();
        $parentToken = $fakeParentToken = new Twig_Token(Twig_Token::STRING_TYPE, '__parent__', $token->getLine());
        if ($parent instanceof Twig_Node_Expression_Constant) {
            $parentToken = new Twig_Token(Twig_Token::STRING_TYPE, $parent->getAttribute('value'), $token->getLine());
        } elseif ($parent instanceof Twig_Node_Expression_Name) {
            $parentToken = new Twig_Token(Twig_Token::NAME_TYPE, $parent->getAttribute('name'), $token->getLine());
        }
        $stream->injectTokens([
            new Twig_Token(Twig_Token::BLOCK_START_TYPE, '', $token->getLine()),
            new Twig_Token(Twig_Token::NAME_TYPE, 'extends', $token->getLine()),
            $parentToken,
            new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', $token->getLine()),
        ]);
        $module = $this->parser->parse($stream, [$this, 'decideBlockEnd'], true);
        if ($fakeParentToken === $parentToken) {
            $module->setNode('parent', $parent);
        }
        $this->parser->embedTemplate($module);
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        return new Twig_Node_Embed($module->getTemplateName(), $module->getAttribute('index'), $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }
    public function decideBlockEnd(Twig_Token $token)
    {
        return $token->test('endembed');
    }
    public function getTag()
    {
        return 'embed';
    }
}
class_alias('Twig_TokenParser_Embed', 'Twig\TokenParser\EmbedTokenParser', false);
