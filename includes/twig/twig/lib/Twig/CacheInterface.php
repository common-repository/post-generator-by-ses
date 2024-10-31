<?php
interface Twig_CacheInterface
{
    public function generateKey($name, $className);
    public function write($key, $content);
    public function load($key);
    public function getTimestamp($key);
}
class_alias('Twig_CacheInterface', 'Twig\Cache\CacheInterface', false);
