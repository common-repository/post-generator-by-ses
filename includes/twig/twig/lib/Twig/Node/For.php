<?php
class Twig_Node_For extends Twig_Node
{
    protected $loop;
    public function __construct(Twig_Node_Expression_AssignName $keyTarget, Twig_Node_Expression_AssignName $valueTarget, Twig_Node_Expression $seq, Twig_Node_Expression $ifexpr = null, Twig_NodeInterface $body, Twig_NodeInterface $else = null, $lineno, $tag = null)
    {
        $body = new Twig_Node([$body, $this->loop = new Twig_Node_ForLoop($lineno, $tag)]);
        if (null !== $ifexpr) {
            $body = new Twig_Node_If(new Twig_Node([$ifexpr, $body]), null, $lineno, $tag);
        }
        $nodes = ['key_target' => $keyTarget, 'value_target' => $valueTarget, 'seq' => $seq, 'body' => $body];
        if (null !== $else) {
            $nodes['else'] = $else;
        }
        parent::__construct($nodes, ['with_loop' => true, 'ifexpr' => null !== $ifexpr], $lineno, $tag);
    }
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("\$context['_parent'] = \$context;\n")
            ->write("\$context['_seq'] = twig_ensure_traversable(")
            ->subcompile($this->getNode('seq'))
            ->raw(");\n")
        ;
        if ($this->hasNode('else')) {
            $compiler->write("\$context['_iterated'] = false;\n");
        }
        if ($this->getAttribute('with_loop')) {
            $compiler
                ->write("\$context['loop'] = array(\n")
                ->write("  'parent' => \$context['_parent'],\n")
                ->write("  'index0' => 0,\n")
                ->write("  'index'  => 1,\n")
                ->write("  'first'  => true,\n")
                ->write(");\n")
            ;
            if (!$this->getAttribute('ifexpr')) {
                $compiler
                    ->write("if (is_array(\$context['_seq']) || (is_object(\$context['_seq']) && \$context['_seq'] instanceof Countable)) {\n")
                    ->indent()
                    ->write("\$length = count(\$context['_seq']);\n")
                    ->write("\$context['loop']['revindex0'] = \$length - 1;\n")
                    ->write("\$context['loop']['revindex'] = \$length;\n")
                    ->write("\$context['loop']['length'] = \$length;\n")
                    ->write("\$context['loop']['last'] = 1 === \$length;\n")
                    ->outdent()
                    ->write("}\n")
                ;
            }
        }
        $this->loop->setAttribute('else', $this->hasNode('else'));
        $this->loop->setAttribute('with_loop', $this->getAttribute('with_loop'));
        $this->loop->setAttribute('ifexpr', $this->getAttribute('ifexpr'));
        $compiler
            ->write("foreach (\$context['_seq'] as ")
            ->subcompile($this->getNode('key_target'))
            ->raw(' => ')
            ->subcompile($this->getNode('value_target'))
            ->raw(") {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n")
        ;
        if ($this->hasNode('else')) {
            $compiler
                ->write("if (!\$context['_iterated']) {\n")
                ->indent()
                ->subcompile($this->getNode('else'))
                ->outdent()
                ->write("}\n")
            ;
        }
        $compiler->write("\$_parent = \$context['_parent'];\n");
        $compiler->write('unset($context[\'_seq\'], $context[\'_iterated\'], $context[\'' . $this->getNode('key_target')->getAttribute('name') . '\'], $context[\'' . $this->getNode('value_target')->getAttribute('name') . '\'], $context[\'_parent\'], $context[\'loop\']);' . "\n");
        $compiler->write("\$context = array_intersect_key(\$context, \$_parent) + \$_parent;\n");
    }
}
class_alias('Twig_Node_For', 'Twig\Node\ForNode', false);
