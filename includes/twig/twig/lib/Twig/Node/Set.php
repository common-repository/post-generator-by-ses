<?php
class Twig_Node_Set extends Twig_Node implements Twig_NodeCaptureInterface
{
    public function __construct($capture, Twig_NodeInterface $names, Twig_NodeInterface $values, $lineno, $tag = null)
    {
        parent::__construct(['names' => $names, 'values' => $values], ['capture' => $capture, 'safe' => false], $lineno, $tag);
        if ($this->getAttribute('capture')) {
            $this->setAttribute('safe', true);
            $values = $this->getNode('values');
            if ($values instanceof Twig_Node_Text) {
                $this->setNode('values', new Twig_Node_Expression_Constant($values->getAttribute('data'), $values->getTemplateLine()));
                $this->setAttribute('capture', false);
            }
        }
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        if (count($this->getNode('names')) > 1) {
            $compiler->write('list(');
            foreach ($this->getNode('names') as $idx => $node) {
                if ($idx) {
                    $compiler->raw(', ');
                }
                $compiler->subcompile($node);
            }
            $compiler->raw(')');
        } else {
            if ($this->getAttribute('capture')) {
                $compiler
                    ->write("ob_start();\n")
                    ->subcompile($this->getNode('values'))
                ;
            }
            $compiler->subcompile($this->getNode('names'), false);
            if ($this->getAttribute('capture')) {
                $compiler->raw(" = ('' === \$tmp = ob_get_clean()) ? '' : new Twig_Markup(\$tmp, \$this->env->getCharset())");
            }
        }
        if (!$this->getAttribute('capture')) {
            $compiler->raw(' = ');
            if (count($this->getNode('names')) > 1) {
                $compiler->write('array(');
                foreach ($this->getNode('values') as $idx => $value) {
                    if ($idx) {
                        $compiler->raw(', ');
                    }
                    $compiler->subcompile($value);
                }
                $compiler->raw(')');
            } else {
                if ($this->getAttribute('safe')) {
                    $compiler
                        ->raw("('' === \$tmp = ")
                        ->subcompile($this->getNode('values'))
                        ->raw(") ? '' : new Twig_Markup(\$tmp, \$this->env->getCharset())")
                    ;
                } else {
                    $compiler->subcompile($this->getNode('values'));
                }
            }
        }
        $compiler->raw(";\n");
    }
}
class_alias('Twig_Node_Set', 'Twig\Node\SetNode', false);
