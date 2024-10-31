<?php
interface Twig_SourceContextLoaderInterface
{
    public function getSourceContext($name);
}
class_alias('Twig_SourceContextLoaderInterface', 'Twig\Loader\SourceContextLoaderInterface', false);
