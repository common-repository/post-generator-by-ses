<?php
class Twig_Cache_Null implements Twig_CacheInterface
{
    public function generateKey($name, $className)
    {
        return '';
    }
    public function write($key, $content)
    {
    }
    public function load($key)
    {
    }
    public function getTimestamp($key)
    {
        return 0;
    }
}
class_alias('Twig_Cache_Null', 'Twig\Cache\NullCache', false);
