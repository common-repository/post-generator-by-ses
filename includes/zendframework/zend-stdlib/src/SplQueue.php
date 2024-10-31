<?php
namespace Zend\Stdlib;
use Serializable;
class SplQueue extends \SplQueue implements Serializable
{
    public function toArray()
    {
        $array = [];
        foreach ($this as $item) {
            $array[] = $item;
        }
        return $array;
    }
    public function serialize()
    {
        return serialize($this->toArray());
    }
    public function unserialize($data)
    {
        foreach (unserialize($data) as $item) {
            $this->push($item);
        }
    }
}
