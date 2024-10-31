<?php
namespace Pg\ProductEnricher;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\VoidCache;
use Ec\Downloader\Downloader;
use Ec\Ebay\EbayService;
use Pg\Product;
use Pg\Settings;
class EbayApiEnricher implements ProductEnricherInterface
{
    private $downloader;
    private $cache;
    private $lifetime;
    private $sleepSecondsAfterEachCall;
    public function __construct(Downloader $downloader, Cache $cache)
    {
        $this->downloader = $downloader;
        $this->lifetime = get_option(Settings::EBAY_CACHE_LIFETIME);
        $this->cache = $this->lifetime ? $cache : new VoidCache();
        $this->sleepSecondsAfterEachCall = 0.2;
    }
    public function enrich(Product $p)
    {
        $keywords = $p->getUPC() ? $p->getUPC() : $p->getEAN();
        if (!$keywords) {
            throw new \RuntimeException('UPC and EAN empty. cannot search on ebay');
        }
        $key = 'pgebay' . md5($keywords);
        if ($this->cache->contains($key)) {
            $item = $this->cache->fetch($key);
        } else {
            $item = $this->getProductData($keywords);
            $this->cache->save($key, $item, $this->lifetime);
            sleep($this->sleepSecondsAfterEachCall);
        }
        if (empty($item)) {
            throw new \RuntimeException('ebay did not find any match with UPC/EAN');
        }
        $site = 'ebay';
        $p->addPrice($item->sellingStatus->currentPrice->value, $item->sellingStatus->currentPrice->currencyId, $site);
        $p->addUrl($item->viewItemURL, $site);
    }
    private function getProductData($keywords)
    {
        $criteria = [
            'keywords' => $keywords,
            'minPrice' => 1,
            'maxPrice' => 99999.99,
            'condition' => 'New',
            'limit' => 10,
        ];
        $items = $this->ebay()->findBy($criteria);
        if (empty($items[0])) {
            return null;
        }
        $item = $items[0];
        return (object) [
            'sellingStatus' => (object) [
                'currentPrice' => (object) [
                    'value' => $item->sellingStatus->currentPrice->value,
                    'currencyId' => $item->sellingStatus->currentPrice->currencyId,
                ]
            ],
            'viewItemURL' => $item->viewItemURL
        ];
    }
    public function ebay()
    {
        $appId = get_option(Settings::EBAY_APP_ID);
        $certId = get_option(Settings::EBAY_CERT_ID);
        $devId = get_option(Settings::EBAY_DEV_ID);
        $globalId = get_option(Settings::EBAY_GLOBAL_ID);
        if (!$appId || !$certId || !$devId || !$globalId) {
            throw new \InvalidArgumentException('Ebay data not added. Api options missing');
        }
        return new EbayService([
            'credentials' => [
                'appId' => $appId,
                'certId' => $certId,
                'devId' => $devId,
            ],
            'campaign' => '',
            'globalId' => $globalId
        ]);
    }
}
