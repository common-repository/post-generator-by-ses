<?php
namespace Zend\Stdlib;
use Countable;
use Iterator;
class PriorityList implements Iterator, Countable
{
    const EXTR_DATA     = 0x00000001;
    const EXTR_PRIORITY = 0x00000002;
    const EXTR_BOTH     = 0x00000003;
    protected $items = [];
    protected $serial = 0;
    protected $isLIFO = 1;
    protected $count = 0;
    protected $sorted = false;
    public function insert($name, $value, $priority = 0)
    {
        if (! isset($this->items[$name])) {
            $this->count++;
        }
        $this->sorted = false;
        $this->items[$name] = [
            'data'     => $value,
            'priority' => (int) $priority,
            'serial'   => $this->serial++,
        ];
    }
    public function setPriority($name, $priority)
    {
        if (! isset($this->items[$name])) {
            throw new \Exception("item $name not found");
        }
        $this->items[$name]['priority'] = (int) $priority;
        $this->sorted                   = false;
        return $this;
    }
    public function remove($name)
    {
        if (isset($this->items[$name])) {
            $this->count--;
        }
        unset($this->items[$name]);
    }
    public function clear()
    {
        $this->items  = [];
        $this->serial = 0;
        $this->count  = 0;
        $this->sorted = false;
    }
    public function get($name)
    {
        if (! isset($this->items[$name])) {
            return;
        }
        return $this->items[$name]['data'];
    }
    protected function sort()
    {
        if (! $this->sorted) {
            uasort($this->items, [$this, 'compare']);
            $this->sorted = true;
        }
    }
    protected function compare(array $item1, array $item2)
    {
        return ($item1['priority'] === $item2['priority'])
            ? ($item1['serial'] > $item2['serial'] ? -1 : 1) * $this->isLIFO
            : ($item1['priority'] > $item2['priority'] ? -1 : 1);
    }
    public function isLIFO($flag = null)
    {
        if ($flag !== null) {
            $isLifo = $flag === true ? 1 : -1;
            if ($isLifo !== $this->isLIFO) {
                $this->isLIFO = $isLifo;
                $this->sorted = false;
            }
        }
        return 1 === $this->isLIFO;
    }
    public function rewind()
    {
        $this->sort();
        reset($this->items);
    }
    public function current()
    {
        $this->sorted || $this->sort();
        $node = current($this->items);
        return $node ? $node['data'] : false;
    }
    public function key()
    {
        $this->sorted || $this->sort();
        return key($this->items);
    }
    public function next()
    {
        $node = next($this->items);
        return $node ? $node['data'] : false;
    }
    public function valid()
    {
        return current($this->items) !== false;
    }
    public function getIterator()
    {
        return clone $this;
    }
    public function count()
    {
        return $this->count;
    }
    public function toArray($flag = self::EXTR_DATA)
    {
        $this->sort();
        if ($flag == self::EXTR_BOTH) {
            return $this->items;
        }
        return array_map(
            function ($item) use ($flag) {
                return ($flag == PriorityList::EXTR_PRIORITY) ? $item['priority'] : $item['data'];
            },
            $this->items
        );
    }
}
