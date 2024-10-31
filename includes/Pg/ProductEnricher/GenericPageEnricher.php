<?php
namespace Pg\ProductEnricher;
use Doctrine\Common\Cache\Cache;
use Ec\Downloader\Downloader;
use Pg\Product;
class GenericPageEnricher implements ProductEnricherInterface
{
    private $downloader;
    private $cache;
    public function __construct(Downloader $downloader, Cache $cache)
    {
        $this->downloader = $downloader;
        $this->cache = $cache;
    }
    public function enrich(Product $p)
    {
        throw new \RuntimeException('TODO');
    }
}
