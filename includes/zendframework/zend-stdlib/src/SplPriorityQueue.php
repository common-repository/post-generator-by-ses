<?php
namespace Zend\Stdlib;
use Serializable;
class SplPriorityQueue extends \SplPriorityQueue implements Serializable
{
    protected $serial = PHP_INT_MAX;
    public function insert($datum, $priority)
    {
        if (! is_array($priority)) {
            $priority = [$priority, $this->serial--];
        }
        parent::insert($datum, $priority);
    }
    public function toArray()
    {
        $array = [];
        foreach (clone $this as $item) {
            $array[] = $item;
        }
        return $array;
    }
    public function serialize()
    {
        $clone = clone $this;
        $clone->setExtractFlags(self::EXTR_BOTH);
        $data = [];
        foreach ($clone as $item) {
            $data[] = $item;
        }
        return serialize($data);
    }
    public function unserialize($data)
    {
        $this->serial = PHP_INT_MAX;
        foreach (unserialize($data) as $item) {
            $this->serial--;
            $this->insert($item['data'], $item['priority']);
        }
    }
}
