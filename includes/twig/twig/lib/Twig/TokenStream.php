<?php
class Twig_TokenStream
{
    protected $tokens;
    protected $current = 0;
    protected $filename;
    private $source;
    public function __construct(array $tokens, $name = null, $source = null)
    {
        if (!$name instanceof Twig_Source) {
            if (null !== $name || null !== $source) {
                @trigger_error(sprintf('Passing a string as the $name argument of %s() is deprecated since version 1.27. Pass a Twig_Source instance instead.', __METHOD__), E_USER_DEPRECATED);
            }
            $this->source = new Twig_Source($source, $name);
        } else {
            $this->source = $name;
        }
        $this->tokens = $tokens;
        $this->filename = $this->source->getName();
    }
    public function __toString()
    {
        return implode("\n", $this->tokens);
    }
    public function injectTokens(array $tokens)
    {
        $this->tokens = array_merge(array_slice($this->tokens, 0, $this->current), $tokens, array_slice($this->tokens, $this->current));
    }
    public function next()
    {
        if (!isset($this->tokens[++$this->current])) {
            throw new Twig_Error_Syntax('Unexpected end of template.', $this->tokens[$this->current - 1]->getLine(), $this->source);
        }
        return $this->tokens[$this->current - 1];
    }
    public function nextIf($primary, $secondary = null)
    {
        if ($this->tokens[$this->current]->test($primary, $secondary)) {
            return $this->next();
        }
    }
    public function expect($type, $value = null, $message = null)
    {
        $token = $this->tokens[$this->current];
        if (!$token->test($type, $value)) {
            $line = $token->getLine();
            throw new Twig_Error_Syntax(
                sprintf(
                '%sUnexpected token "%s" of value "%s" ("%s" expected%s).',
                $message ? $message . '. ' : '',
                Twig_Token::typeToEnglish($token->getType()),
                $token->getValue(),
                Twig_Token::typeToEnglish($type),
                $value ? sprintf(' with value "%s"', $value) : ''
            ),
                $line,
                $this->source
            );
        }
        $this->next();
        return $token;
    }
    public function look($number = 1)
    {
        if (!isset($this->tokens[$this->current + $number])) {
            throw new Twig_Error_Syntax('Unexpected end of template.', $this->tokens[$this->current + $number - 1]->getLine(), $this->source);
        }
        return $this->tokens[$this->current + $number];
    }
    public function test($primary, $secondary = null)
    {
        return $this->tokens[$this->current]->test($primary, $secondary);
    }
    public function isEOF()
    {
        return Twig_Token::EOF_TYPE === $this->tokens[$this->current]->getType();
    }
    public function getCurrent()
    {
        return $this->tokens[$this->current];
    }
    public function getFilename()
    {
        @trigger_error(sprintf('The %s() method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', __METHOD__), E_USER_DEPRECATED);
        return $this->source->getName();
    }
    public function getSource()
    {
        @trigger_error(sprintf('The %s() method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', __METHOD__), E_USER_DEPRECATED);
        return $this->source->getCode();
    }
    public function getSourceContext()
    {
        return $this->source;
    }
}
class_alias('Twig_TokenStream', 'Twig\TokenStream', false);
