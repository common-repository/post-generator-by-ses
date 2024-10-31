<?php
namespace Zend\Stdlib;
interface ParameterObjectInterface
{
    public function __set($key, $value);
    public function __get($key);
    public function __isset($key);
    public function __unset($key);
}
