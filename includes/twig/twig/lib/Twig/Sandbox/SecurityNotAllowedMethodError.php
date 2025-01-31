<?php
class Twig_Sandbox_SecurityNotAllowedMethodError extends Twig_Sandbox_SecurityError
{
    private $className;
    private $methodName;
    public function __construct($message, $className, $methodName, $lineno = -1, $filename = null, Exception $previous = null)
    {
        parent::__construct($message, $lineno, $filename, $previous);
        $this->className = $className;
        $this->methodName = $methodName;
    }
    public function getClassName()
    {
        return $this->className;
    }
    public function getMethodName()
    {
        return $this->methodName;
    }
}
class_alias('Twig_Sandbox_SecurityNotAllowedMethodError', 'Twig\Sandbox\SecurityNotAllowedMethodError', false);
