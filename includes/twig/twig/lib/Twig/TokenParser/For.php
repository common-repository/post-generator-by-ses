<?php
class Twig_TokenParser_For extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $targets = $this->parser->getExpressionParser()->parseAssignmentExpression();
        $stream->expect(Twig_Token::OPERATOR_TYPE, 'in');
        $seq = $this->parser->getExpressionParser()->parseExpression();
        $ifexpr = null;
        if ($stream->nextIf(Twig_Token::NAME_TYPE, 'if')) {
            $ifexpr = $this->parser->getExpressionParser()->parseExpression();
        }
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideForFork']);
        if ('else' == $stream->next()->getValue()) {
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
            $else = $this->parser->subparse([$this, 'decideForEnd'], true);
        } else {
            $else = null;
        }
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        if (count($targets) > 1) {
            $keyTarget = $targets->getNode(0);
            $keyTarget = new Twig_Node_Expression_AssignName($keyTarget->getAttribute('name'), $keyTarget->getTemplateLine());
            $valueTarget = $targets->getNode(1);
            $valueTarget = new Twig_Node_Expression_AssignName($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        } else {
            $keyTarget = new Twig_Node_Expression_AssignName('_key', $lineno);
            $valueTarget = $targets->getNode(0);
            $valueTarget = new Twig_Node_Expression_AssignName($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        }
        if ($ifexpr) {
            $this->checkLoopUsageCondition($stream, $ifexpr);
            $this->checkLoopUsageBody($stream, $body);
        }
        return new Twig_Node_For($keyTarget, $valueTarget, $seq, $ifexpr, $body, $else, $lineno, $this->getTag());
    }
    public function decideForFork(Twig_Token $token)
    {
        return $token->test(['else', 'endfor']);
    }
    public function decideForEnd(Twig_Token $token)
    {
        return $token->test('endfor');
    }
    protected function checkLoopUsageCondition(Twig_TokenStream $stream, Twig_NodeInterface $node)
    {
        if ($node instanceof Twig_Node_Expression_GetAttr && $node->getNode('node') instanceof Twig_Node_Expression_Name && 'loop' == $node->getNode('node')->getAttribute('name')) {
            throw new Twig_Error_Syntax('The "loop" variable cannot be used in a looping condition.', $node->getTemplateLine(), $stream->getSourceContext());
        }
        foreach ($node as $n) {
            if (!$n) {
                continue;
            }
            $this->checkLoopUsageCondition($stream, $n);
        }
    }
    protected function checkLoopUsageBody(Twig_TokenStream $stream, Twig_NodeInterface $node)
    {
        if ($node instanceof Twig_Node_Expression_GetAttr && $node->getNode('node') instanceof Twig_Node_Expression_Name && 'loop' == $node->getNode('node')->getAttribute('name')) {
            $attribute = $node->getNode('attribute');
            if ($attribute instanceof Twig_Node_Expression_Constant && in_array($attribute->getAttribute('value'), ['length', 'revindex0', 'revindex', 'last'])) {
                throw new Twig_Error_Syntax(sprintf('The "loop.%s" variable is not defined when looping with a condition.', $attribute->getAttribute('value')), $node->getTemplateLine(), $stream->getSourceContext());
            }
        }
        if ($node instanceof Twig_Node_For) {
            return;
        }
        foreach ($node as $n) {
            if (!$n) {
                continue;
            }
            $this->checkLoopUsageBody($stream, $n);
        }
    }
    public function getTag()
    {
        return 'for';
    }
}
class_alias('Twig_TokenParser_For', 'Twig\TokenParser\ForTokenParser', false);
