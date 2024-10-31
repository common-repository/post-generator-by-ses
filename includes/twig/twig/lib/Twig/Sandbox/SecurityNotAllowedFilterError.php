<?php
class Twig_Sandbox_SecurityNotAllowedFilterError extends Twig_Sandbox_SecurityError
{
    private $filterName;
    public function __construct($message, $functionName, $lineno = -1, $filename = null, Exception $previous = null)
    {
        parent::__construct($message, $lineno, $filename, $previous);
        $this->filterName = $functionName;
    }
    public function getFilterName()
    {
        return $this->filterName;
    }
}
class_alias('Twig_Sandbox_SecurityNotAllowedFilterError', 'Twig\Sandbox\SecurityNotAllowedFilterError', false);
