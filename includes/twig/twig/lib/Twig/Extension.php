<?php
abstract class Twig_Extension implements Twig_ExtensionInterface
{
    public function initRuntime(Twig_Environment $environment)
    {
    }
    public function getTokenParsers()
    {
        return [];
    }
    public function getNodeVisitors()
    {
        return [];
    }
    public function getFilters()
    {
        return [];
    }
    public function getTests()
    {
        return [];
    }
    public function getFunctions()
    {
        return [];
    }
    public function getOperators()
    {
        return [];
    }
    public function getGlobals()
    {
        return [];
    }
    public function getName()
    {
        return get_class($this);
    }
}
class_alias('Twig_Extension', 'Twig\Extension\AbstractExtension', false);
class_exists('Twig_Environment');
