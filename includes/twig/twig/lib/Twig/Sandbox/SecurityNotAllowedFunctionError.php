<?php
class Twig_Sandbox_SecurityNotAllowedFunctionError extends Twig_Sandbox_SecurityError
{
    private $functionName;
    public function __construct($message, $functionName, $lineno = -1, $filename = null, Exception $previous = null)
    {
        parent::__construct($message, $lineno, $filename, $previous);
        $this->functionName = $functionName;
    }
    public function getFunctionName()
    {
        return $this->functionName;
    }
}
class_alias('Twig_Sandbox_SecurityNotAllowedFunctionError', 'Twig\Sandbox\SecurityNotAllowedFunctionError', false);
