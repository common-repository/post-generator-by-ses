<?php
namespace Zend\Stdlib;
use ArrayIterator;
use ArrayObject as PhpArrayObject;
class ArrayStack extends PhpArrayObject
{
    public function getIterator()
    {
        $array = $this->getArrayCopy();
        return new ArrayIterator(array_reverse($array));
    }
}
