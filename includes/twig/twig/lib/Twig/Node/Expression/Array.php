<?php
class Twig_Node_Expression_Array extends Twig_Node_Expression
{
    protected $index;
    public function __construct(array $elements, $lineno)
    {
        parent::__construct($elements, [], $lineno);
        $this->index = -1;
        foreach ($this->getKeyValuePairs() as $pair) {
            if ($pair['key'] instanceof Twig_Node_Expression_Constant && ctype_digit((string) $pair['key']->getAttribute('value')) && $pair['key']->getAttribute('value') > $this->index) {
                $this->index = $pair['key']->getAttribute('value');
            }
        }
    }
    public function getKeyValuePairs()
    {
        $pairs = [];
        foreach (array_chunk($this->nodes, 2) as $pair) {
            $pairs[] = [
                'key' => $pair[0],
                'value' => $pair[1],
            ];
        }
        return $pairs;
    }
    public function hasElement(Twig_Node_Expression $key)
    {
        foreach ($this->getKeyValuePairs() as $pair) {
            if ((string) $key === (string) $pair['key']) {
                return true;
            }
        }
        return false;
    }
    public function addElement(Twig_Node_Expression $value, Twig_Node_Expression $key = null)
    {
        if (null === $key) {
            $key = new Twig_Node_Expression_Constant(++$this->index, $value->getTemplateLine());
        }
        array_push($this->nodes, $key, $value);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->raw('array(');
        $first = true;
        foreach ($this->getKeyValuePairs() as $pair) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $first = false;
            $compiler
                ->subcompile($pair['key'])
                ->raw(' => ')
                ->subcompile($pair['value'])
            ;
        }
        $compiler->raw(')');
    }
}
class_alias('Twig_Node_Expression_Array', 'Twig\Node\Expression\ArrayExpression', false);
