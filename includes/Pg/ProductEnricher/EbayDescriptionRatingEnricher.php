<?php
namespace Pg\ProductEnricher;
use Doctrine\Common\Cache\Cache;
use Ec\Downloader\Downloader;
use Pg\Product;
use Pg\Settings;
class EbayDescriptionRatingEnricher implements ProductEnricherInterface
{
    private $downloader;
    private $cache;
    private $lifetime;
    public function __construct(Downloader $downloader, Cache $cache)
    {
        $this->downloader = $downloader;
        $this->cache = $cache;
        $this->lifetime = get_option(Settings::EBAY_CACHE_LIFETIME);
    }
    public function enrich(Product $p)
    {
        throw new \RuntimeException('TODO');
        $urls = $p->getUrls();
        if (!isset($urls['ebay'])) {
            throw new \RuntimeException('ebay URL not found. description parsing skipped.');
        }
        $url = $urls['ebay'];
    }
}
