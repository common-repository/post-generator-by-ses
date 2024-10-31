<?php
class Twig_Error extends Exception
{
    protected $lineno;
    protected $filename;
    protected $rawMessage;
    protected $previous;
    private $sourcePath;
    private $sourceCode;
    public function __construct($message, $lineno = -1, $source = null, Exception $previous = null)
    {
        if (null === $source) {
            $name = null;
        } elseif (!$source instanceof Twig_Source) {
            $name = $source;
        } else {
            $name = $source->getName();
            $this->sourceCode = $source->getCode();
            $this->sourcePath = $source->getPath();
        }
        if (PHP_VERSION_ID < 50300) {
            $this->previous = $previous;
            parent::__construct('');
        } else {
            parent::__construct('', 0, $previous);
        }
        $this->lineno = $lineno;
        $this->filename = $name;
        if (-1 === $lineno || null === $name || null === $this->sourcePath) {
            $this->guessTemplateInfo();
        }
        $this->rawMessage = $message;
        $this->updateRepr();
    }
    public function getRawMessage()
    {
        return $this->rawMessage;
    }
    public function getTemplateFile()
    {
        @trigger_error(sprintf('The "%s" method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', __METHOD__), E_USER_DEPRECATED);
        return $this->filename;
    }
    public function setTemplateFile($name)
    {
        @trigger_error(sprintf('The "%s" method is deprecated since version 1.27 and will be removed in 2.0. Use setSourceContext() instead.', __METHOD__), E_USER_DEPRECATED);
        $this->filename = $name;
        $this->updateRepr();
    }
    public function getTemplateName()
    {
        @trigger_error(sprintf('The "%s" method is deprecated since version 1.29 and will be removed in 2.0. Use getSourceContext() instead.', __METHOD__), E_USER_DEPRECATED);
        return $this->filename;
    }
    public function setTemplateName($name)
    {
        @trigger_error(sprintf('The "%s" method is deprecated since version 1.29 and will be removed in 2.0. Use setSourceContext() instead.', __METHOD__), E_USER_DEPRECATED);
        $this->filename = $name;
        $this->sourceCode = $this->sourcePath = null;
        $this->updateRepr();
    }
    public function getTemplateLine()
    {
        return $this->lineno;
    }
    public function setTemplateLine($lineno)
    {
        $this->lineno = $lineno;
        $this->updateRepr();
    }
    public function getSourceContext()
    {
        return $this->filename ? new Twig_Source($this->sourceCode, $this->filename, $this->sourcePath) : null;
    }
    public function setSourceContext(Twig_Source $source = null)
    {
        if (null === $source) {
            $this->sourceCode = $this->filename = $this->sourcePath = null;
        } else {
            $this->sourceCode = $source->getCode();
            $this->filename = $source->getName();
            $this->sourcePath = $source->getPath();
        }
        $this->updateRepr();
    }
    public function guess()
    {
        $this->guessTemplateInfo();
        $this->updateRepr();
    }
    public function __call($method, $arguments)
    {
        if ('getprevious' == strtolower($method)) {
            return $this->previous;
        }
        throw new BadMethodCallException(sprintf('Method "Twig_Error::%s()" does not exist.', $method));
    }
    public function appendMessage($rawMessage)
    {
        $this->rawMessage .= $rawMessage;
        $this->updateRepr();
    }
    protected function updateRepr()
    {
        $this->message = $this->rawMessage;
        if ($this->sourcePath && $this->lineno > 0) {
            $this->file = $this->sourcePath;
            $this->line = $this->lineno;
            return;
        }
        $dot = false;
        if ('.' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
            $dot = true;
        }
        $questionMark = false;
        if ('?' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
            $questionMark = true;
        }
        if ($this->filename) {
            if (is_string($this->filename) || (is_object($this->filename) && method_exists($this->filename, '__toString'))) {
                $name = sprintf('"%s"', $this->filename);
            } else {
                $name = json_encode($this->filename);
            }
            $this->message .= sprintf(' in %s', $name);
        }
        if ($this->lineno && $this->lineno >= 0) {
            $this->message .= sprintf(' at line %d', $this->lineno);
        }
        if ($dot) {
            $this->message .= '.';
        }
        if ($questionMark) {
            $this->message .= '?';
        }
    }
    protected function guessTemplateInfo()
    {
        $template = null;
        $templateClass = null;
        if (PHP_VERSION_ID >= 50306) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);
        } else {
            $backtrace = debug_backtrace();
        }
        foreach ($backtrace as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Twig_Template && 'Twig_Template' !== get_class($trace['object'])) {
                $currentClass = get_class($trace['object']);
                $isEmbedContainer = 0 === strpos($templateClass, $currentClass);
                if (null === $this->filename || ($this->filename == $trace['object']->getTemplateName() && !$isEmbedContainer)) {
                    $template = $trace['object'];
                    $templateClass = get_class($trace['object']);
                }
            }
        }
        if (null !== $template && null === $this->filename) {
            $this->filename = $template->getTemplateName();
        }
        if (null !== $template && null === $this->sourcePath) {
            $src = $template->getSourceContext();
            $this->sourceCode = $src->getCode();
            $this->sourcePath = $src->getPath();
        }
        if (null === $template || $this->lineno > -1) {
            return;
        }
        $r = new ReflectionObject($template);
        $file = $r->getFileName();
        $exceptions = [$e = $this];
        while (($e instanceof self || method_exists($e, 'getPrevious')) && $e = $e->getPrevious()) {
            $exceptions[] = $e;
        }
        while ($e = array_pop($exceptions)) {
            $traces = $e->getTrace();
            array_unshift($traces, ['file' => $e->getFile(), 'line' => $e->getLine()]);
            while ($trace = array_shift($traces)) {
                if (!isset($trace['file']) || !isset($trace['line']) || $file != $trace['file']) {
                    continue;
                }
                foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
                    if ($codeLine <= $trace['line']) {
                        $this->lineno = $templateLine;
                        return;
                    }
                }
            }
        }
    }
}
class_alias('Twig_Error', 'Twig\Error\Error', false);
class_exists('Twig_Source');
