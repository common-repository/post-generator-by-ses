<?php
namespace Google\Auth\Cache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
final class MemoryCacheItemPool implements CacheItemPoolInterface
{
    private $items;
    private $deferredItems;
    public function getItem($key)
    {
        return current($this->getItems([$key]));
    }
    public function getItems(array $keys = [])
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->hasItem($key) ? clone $this->items[$key] : new Item($key);
        }
        return $items;
    }
    public function hasItem($key)
    {
        $this->isValidKey($key);
        return isset($this->items[$key]) && $this->items[$key]->isHit();
    }
    public function clear()
    {
        $this->items = [];
        $this->deferredItems = [];
        return true;
    }
    public function deleteItem($key)
    {
        return $this->deleteItems([$key]);
    }
    public function deleteItems(array $keys)
    {
        array_walk($keys, [$this, 'isValidKey']);
        foreach ($keys as $key) {
            unset($this->items[$key]);
        }
        return true;
    }
    public function save(CacheItemInterface $item)
    {
        $this->items[$item->getKey()] = $item;
        return true;
    }
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferredItems[$item->getKey()] = $item;
        return true;
    }
    public function commit()
    {
        foreach ($this->deferredItems as $item) {
            $this->save($item);
        }
        $this->deferredItems = [];
        return true;
    }
    private function isValidKey($key)
    {
        $invalidCharacters = '{}()/\\\\@:';
        if (!is_string($key) || preg_match("#[$invalidCharacters]#", $key)) {
            throw new InvalidArgumentException('The provided key is not valid: ' . var_export($key, true));
        }
        return true;
    }
}
