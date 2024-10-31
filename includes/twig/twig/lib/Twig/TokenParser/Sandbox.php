<?php
class Twig_TokenParser_Sandbox extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        if (!$body instanceof Twig_Node_Include) {
            foreach ($body as $node) {
                if ($node instanceof Twig_Node_Text && ctype_space($node->getAttribute('data'))) {
                    continue;
                }
                if (!$node instanceof Twig_Node_Include) {
                    throw new Twig_Error_Syntax('Only "include" tags are allowed within a "sandbox" section.', $node->getTemplateLine(), $stream->getSourceContext());
                }
            }
        }
        return new Twig_Node_Sandbox($body, $token->getLine(), $this->getTag());
    }
    public function decideBlockEnd(Twig_Token $token)
    {
        return $token->test('endsandbox');
    }
    public function getTag()
    {
        return 'sandbox';
    }
}
class_alias('Twig_TokenParser_Sandbox', 'Twig\TokenParser\SandboxTokenParser', false);
