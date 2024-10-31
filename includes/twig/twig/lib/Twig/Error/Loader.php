<?php
class Twig_Error_Loader extends Twig_Error
{
    public function __construct($message, $lineno = -1, $source = null, Exception $previous = null)
    {
        if (PHP_VERSION_ID < 50300) {
            $this->previous = $previous;
            Exception::__construct('');
        } else {
            Exception::__construct('', 0, $previous);
        }
        $this->appendMessage($message);
        $this->setTemplateLine(false);
    }
}
class_alias('Twig_Error_Loader', 'Twig\Error\LoaderError', false);
