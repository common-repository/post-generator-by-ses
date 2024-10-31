<?php
namespace Google\Auth;
use Psr\Cache\CacheItemPoolInterface;
class FetchAuthTokenCache implements FetchAuthTokenInterface
{
    use CacheTrait;
    private $fetcher;
    private $cacheConfig;
    private $cache;
    public function __construct(
        FetchAuthTokenInterface $fetcher,
        array $cacheConfig = null,
        CacheItemPoolInterface $cache
    ) {
        $this->fetcher = $fetcher;
        $this->cache = $cache;
        $this->cacheConfig = array_merge([
            'lifetime' => 1500,
            'prefix' => '',
        ], (array) $cacheConfig);
    }
    public function fetchAuthToken(callable $httpHandler = null)
    {
        $cacheKey = $this->fetcher->getCacheKey();
        $cached = $this->getCachedValue($cacheKey);
        if (!empty($cached)) {
            return ['access_token' => $cached];
        }
        $auth_token = $this->fetcher->fetchAuthToken($httpHandler);
        if (isset($auth_token['access_token'])) {
            $this->setCachedValue($cacheKey, $auth_token['access_token']);
        }
        return $auth_token;
    }
    public function getCacheKey()
    {
        return $this->getFullCacheKey($this->fetcher->getCacheKey());
    }
    public function getLastReceivedToken()
    {
        return $this->fetcher->getLastReceivedToken();
    }
}
