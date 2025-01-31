<?php
class Twig_Compiler implements Twig_CompilerInterface
{
    protected $lastLine;
    protected $source;
    protected $indentation;
    protected $env;
    protected $debugInfo = [];
    protected $sourceOffset;
    protected $sourceLine;
    protected $filename;
    private $varNameSalt = 0;
    public function __construct(Twig_Environment $env)
    {
        $this->env = $env;
    }
    public function getFilename()
    {
        @trigger_error(sprintf('The %s() method is deprecated since version 1.25 and will be removed in 2.0.', __FUNCTION__), E_USER_DEPRECATED);
        return $this->filename;
    }
    public function getEnvironment()
    {
        return $this->env;
    }
    public function getSource()
    {
        return $this->source;
    }
    public function compile(Twig_NodeInterface $node, $indentation = 0)
    {
        $this->lastLine = null;
        $this->source = '';
        $this->debugInfo = [];
        $this->sourceOffset = 0;
        $this->sourceLine = 1;
        $this->indentation = $indentation;
        $this->varNameSalt = 0;
        if ($node instanceof Twig_Node_Module) {
            $this->filename = $node->getTemplateName();
        }
        $node->compile($this);
        return $this;
    }
    public function subcompile(Twig_NodeInterface $node, $raw = true)
    {
        if (false === $raw) {
            $this->source .= str_repeat(' ', $this->indentation * 4);
        }
        $node->compile($this);
        return $this;
    }
    public function raw($string)
    {
        $this->source .= $string;
        return $this;
    }
    public function write()
    {
        $strings = func_get_args();
        foreach ($strings as $string) {
            $this->source .= str_repeat(' ', $this->indentation * 4) . $string;
        }
        return $this;
    }
    public function addIndentation()
    {
        @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 1.27 and will be removed in 2.0. Use write(\'\') instead.', E_USER_DEPRECATED);
        $this->source .= str_repeat(' ', $this->indentation * 4);
        return $this;
    }
    public function string($value)
    {
        $this->source .= sprintf('"%s"', addcslashes($value, "\0\t\"\$\\"));
        return $this;
    }
    public function repr($value)
    {
        if (is_int($value) || is_float($value)) {
            if (false !== $locale = setlocale(LC_NUMERIC, '0')) {
                setlocale(LC_NUMERIC, 'C');
            }
            $this->raw($value);
            if (false !== $locale) {
                setlocale(LC_NUMERIC, $locale);
            }
        } elseif (null === $value) {
            $this->raw('null');
        } elseif (is_bool($value)) {
            $this->raw($value ? 'true' : 'false');
        } elseif (is_array($value)) {
            $this->raw('array(');
            $first = true;
            foreach ($value as $key => $v) {
                if (!$first) {
                    $this->raw(', ');
                }
                $first = false;
                $this->repr($key);
                $this->raw(' => ');
                $this->repr($v);
            }
            $this->raw(')');
        } else {
            $this->string($value);
        }
        return $this;
    }
    public function addDebugInfo(Twig_NodeInterface $node)
    {
        if ($node->getTemplateLine() != $this->lastLine) {
            $this->write(sprintf("// line %d\n", $node->getTemplateLine()));
            if (((int) ini_get('mbstring.func_overload')) & 2) {
                @trigger_error('Support for having "mbstring.func_overload" different from 0 is deprecated version 1.29 and will be removed in 2.0.', E_USER_DEPRECATED);
                $this->sourceLine += mb_substr_count(mb_substr($this->source, $this->sourceOffset), "\n");
            } else {
                $this->sourceLine += substr_count($this->source, "\n", $this->sourceOffset);
            }
            $this->sourceOffset = strlen($this->source);
            $this->debugInfo[$this->sourceLine] = $node->getTemplateLine();
            $this->lastLine = $node->getTemplateLine();
        }
        return $this;
    }
    public function getDebugInfo()
    {
        ksort($this->debugInfo);
        return $this->debugInfo;
    }
    public function indent($step = 1)
    {
        $this->indentation += $step;
        return $this;
    }
    public function outdent($step = 1)
    {
        if ($this->indentation < $step) {
            throw new LogicException('Unable to call outdent() as the indentation would become negative.');
        }
        $this->indentation -= $step;
        return $this;
    }
    public function getVarName()
    {
        return sprintf('__internal_%s', hash('sha256', __METHOD__ . $this->varNameSalt++));
    }
}
class_alias('Twig_Compiler', 'Twig\Compiler', false);
class_exists('Twig_Node');
