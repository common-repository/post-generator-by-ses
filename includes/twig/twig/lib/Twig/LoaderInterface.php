<?php
interface Twig_LoaderInterface
{
    public function getSource($name);
    public function getCacheKey($name);
    public function isFresh($name, $time);
}
class_alias('Twig_LoaderInterface', 'Twig\Loader\LoaderInterface', false);
