<?php
namespace Google\Auth\Cache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
class SysVCacheItemPool implements CacheItemPoolInterface
{
    const VAR_KEY = 1;
    const DEFAULT_PROJ = 'A';
    const DEFAULT_MEMSIZE = 10000;
    const DEFAULT_PERM = 0600;
    private $sysvKey;
    private $items;
    private $deferredItems;
    private $options;
    private function saveCurrentItems()
    {
        $shmid = shm_attach(
            $this->sysvKey,
            $this->options['memsize'],
            $this->options['perm']
        );
        if ($shmid !== false) {
            $ret = shm_put_var(
                $shmid,
                $this->options['variableKey'],
                $this->items
            );
            shm_detach($shmid);
            return $ret;
        }
        return false;
    }
    private function loadItems()
    {
        $shmid = shm_attach(
            $this->sysvKey,
            $this->options['memsize'],
            $this->options['perm']
        );
        if ($shmid !== false) {
            $data = @shm_get_var($shmid, $this->options['variableKey']);
            if (!empty($data)) {
                $this->items = $data;
            } else {
                $this->items = [];
            }
            shm_detach($shmid);
            return true;
        }
        return false;
    }
    public function __construct($options = [])
    {
        if (! extension_loaded('sysvshm')) {
            throw \RuntimeException(
                'sysvshm extension is required to use this ItemPool'
            );
        }
        $this->options = $options + [
            'variableKey' => self::VAR_KEY,
            'proj' => self::DEFAULT_PROJ,
            'memsize' => self::DEFAULT_MEMSIZE,
            'perm' => self::DEFAULT_PERM
        ];
        $this->items = [];
        $this->deferredItems = [];
        $this->sysvKey = ftok(__FILE__, $this->options['proj']);
        $this->loadItems();
    }
    public function getItem($key)
    {
        $this->loadItems();
        return current($this->getItems([$key]));
    }
    public function getItems(array $keys = [])
    {
        $this->loadItems();
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->hasItem($key) ?
                clone $this->items[$key] :
                new Item($key);
        }
        return $items;
    }
    public function hasItem($key)
    {
        $this->loadItems();
        return isset($this->items[$key]) && $this->items[$key]->isHit();
    }
    public function clear()
    {
        $this->items = [];
        $this->deferredItems = [];
        return $this->saveCurrentItems();
    }
    public function deleteItem($key)
    {
        return $this->deleteItems([$key]);
    }
    public function deleteItems(array $keys)
    {
        foreach ($keys as $key) {
            unset($this->items[$key]);
        }
        return $this->saveCurrentItems();
    }
    public function save(CacheItemInterface $item)
    {
        $this->items[$item->getKey()] = $item;
        return $this->saveCurrentItems();
    }
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferredItems[$item->getKey()] = $item;
        return true;
    }
    public function commit()
    {
        foreach ($this->deferredItems as $item) {
            if ($this->save($item) === false) {
                return false;
            }
        }
        $this->deferredItems = [];
        return true;
    }
}
