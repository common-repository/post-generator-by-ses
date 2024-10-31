<?php
class Twig_Source
{
    private $code;
    private $name;
    private $path;
    public function __construct($code, $name, $path = '')
    {
        $this->code = $code;
        $this->name = $name;
        $this->path = $path;
    }
    public function getCode()
    {
        return $this->code;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getPath()
    {
        return $this->path;
    }
}
class_alias('Twig_Source', 'Twig\Source', false);
