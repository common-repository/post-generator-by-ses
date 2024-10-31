<?php
class Twig_FactoryRuntimeLoader implements Twig_RuntimeLoaderInterface
{
    private $map;
    public function __construct($map = [])
    {
        $this->map = $map;
    }
    public function load($class)
    {
        if (isset($this->map[$class])) {
            $runtimeFactory = $this->map[$class];
            return $runtimeFactory();
        }
    }
}
class_alias('Twig_FactoryRuntimeLoader', 'Twig\RuntimeLoader\FactoryRuntimeLoader', false);
