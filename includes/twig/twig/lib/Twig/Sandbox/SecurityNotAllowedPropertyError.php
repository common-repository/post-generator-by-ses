<?php
class Twig_Sandbox_SecurityNotAllowedPropertyError extends Twig_Sandbox_SecurityError
{
    private $className;
    private $propertyName;
    public function __construct($message, $className, $propertyName, $lineno = -1, $filename = null, Exception $previous = null)
    {
        parent::__construct($message, $lineno, $filename, $previous);
        $this->className = $className;
        $this->propertyName = $propertyName;
    }
    public function getClassName()
    {
        return $this->className;
    }
    public function getPropertyName()
    {
        return $this->propertyName;
    }
}
class_alias('Twig_Sandbox_SecurityNotAllowedPropertyError', 'Twig\Sandbox\SecurityNotAllowedPropertyError', false);
