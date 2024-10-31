<?php
interface Twig_ExtensionInterface
{
    public function initRuntime(Twig_Environment $environment);
    public function getTokenParsers();
    public function getNodeVisitors();
    public function getFilters();
    public function getTests();
    public function getFunctions();
    public function getOperators();
    public function getGlobals();
    public function getName();
}
class_alias('Twig_ExtensionInterface', 'Twig\Extension\ExtensionInterface', false);
class_exists('Twig_Environment');
