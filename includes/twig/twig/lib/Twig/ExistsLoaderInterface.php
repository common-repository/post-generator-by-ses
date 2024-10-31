<?php
interface Twig_ExistsLoaderInterface
{
    public function exists($name);
}
class_alias('Twig_ExistsLoaderInterface', 'Twig\Loader\ExistsLoaderInterface', false);
