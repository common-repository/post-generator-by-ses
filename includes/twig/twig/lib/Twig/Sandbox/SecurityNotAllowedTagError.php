<?php
class Twig_Sandbox_SecurityNotAllowedTagError extends Twig_Sandbox_SecurityError
{
    private $tagName;
    public function __construct($message, $tagName, $lineno = -1, $filename = null, Exception $previous = null)
    {
        parent::__construct($message, $lineno, $filename, $previous);
        $this->tagName = $tagName;
    }
    public function getTagName()
    {
        return $this->tagName;
    }
}
class_alias('Twig_Sandbox_SecurityNotAllowedTagError', 'Twig\Sandbox\SecurityNotAllowedTagError', false);
