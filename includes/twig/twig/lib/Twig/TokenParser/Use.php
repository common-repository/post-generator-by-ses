<?php
class Twig_TokenParser_Use extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $template = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        if (!$template instanceof Twig_Node_Expression_Constant) {
            throw new Twig_Error_Syntax('The template references in a "use" statement must be a string.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }
        $targets = [];
        if ($stream->nextIf('with')) {
            do {
                $name = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
                $alias = $name;
                if ($stream->nextIf('as')) {
                    $alias = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
                }
                $targets[$name] = new Twig_Node_Expression_Constant($alias, -1);
                if (!$stream->nextIf(Twig_Token::PUNCTUATION_TYPE, ',')) {
                    break;
                }
            } while (true);
        }
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $this->parser->addTrait(new Twig_Node(['template' => $template, 'targets' => new Twig_Node($targets)]));
        return new Twig_Node();
    }
    public function getTag()
    {
        return 'use';
    }
}
class_alias('Twig_TokenParser_Use', 'Twig\TokenParser\UseTokenParser', false);
