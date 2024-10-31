<?php
namespace Zend\Loader;
if (interface_exists('Zend\Loader\SplAutoloader')) {
    return;
}
interface SplAutoloader
{
    public function __construct($options = null);
    public function setOptions($options);
    public function autoload($class);
    public function register();
}
