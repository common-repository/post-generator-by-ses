<?php
namespace Zend\Json;
class Expr
{
    protected $expression;
    public function __construct($expression)
    {
        $this->expression = (string) $expression;
    }
    public function __toString()
    {
        return $this->expression;
    }
}
