<?php
namespace Zend\Loader;
interface ShortNameLocator
{
    public function isLoaded($name);
    public function getClassName($name);
    public function load($name);
}
