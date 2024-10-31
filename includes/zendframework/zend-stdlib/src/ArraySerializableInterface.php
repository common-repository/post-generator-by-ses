<?php
namespace Zend\Stdlib;
interface ArraySerializableInterface
{
    public function exchangeArray(array $array);
    public function getArrayCopy();
}
