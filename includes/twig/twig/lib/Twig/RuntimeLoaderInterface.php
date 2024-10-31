<?php
interface Twig_RuntimeLoaderInterface
{
    public function load($class);
}
class_alias('Twig_RuntimeLoaderInterface', 'Twig\RuntimeLoader\RuntimeLoaderInterface', false);
