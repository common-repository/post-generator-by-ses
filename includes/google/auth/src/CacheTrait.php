<?php
namespace Google\Auth;
trait CacheTrait
{
    private $maxKeyLength = 64;
    private function getCachedValue($k)
    {
        if (is_null($this->cache)) {
            return;
        }
        $key = $this->getFullCacheKey($k);
        if (is_null($key)) {
            return;
        }
        $cacheItem = $this->cache->getItem($key);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }
    }
    private function setCachedValue($k, $v)
    {
        if (is_null($this->cache)) {
            return;
        }
        $key = $this->getFullCacheKey($k);
        if (is_null($key)) {
            return;
        }
        $cacheItem = $this->cache->getItem($key);
        $cacheItem->set($v);
        $cacheItem->expiresAfter($this->cacheConfig['lifetime']);
        return $this->cache->save($cacheItem);
    }
    private function getFullCacheKey($key)
    {
        if (is_null($key)) {
            return;
        }
        $key = $this->cacheConfig['prefix'] . $key;
        $key = preg_replace('|[^a-zA-Z0-9_\.!]|', '', $key);
        if ($this->maxKeyLength && strlen($key) > $this->maxKeyLength) {
            $key = substr(hash('sha256', $key), 0, $this->maxKeyLength);
        }
        return $key;
    }
}
