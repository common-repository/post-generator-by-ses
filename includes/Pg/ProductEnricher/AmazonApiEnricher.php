<?php
namespace Pg\ProductEnricher;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\VoidCache;
use Ec\Amazon\AmazonUtils;
use Pg\Product;
use Pg\Settings;
use ZendService\Amazon\Amazon;
class AmazonApiEnricher implements ProductEnricherInterface
{
    const SITE = 'amazon';
    private $cache;
    private $lifetime;
    private $sleepSecondsAfterEachCall;
    public function __construct(Cache $cache)
    {
        $this->lifetime = get_option(Settings::AMAZON_CACHE_LIFETIME);
        $this->cache = $this->lifetime ? $cache : new VoidCache();
        $this->sleepSecondsAfterEachCall = 1;
    }
    public function enrich(Product $p)
    {
        $asin = AmazonUtils::extractASINFromUrlOrText($p->getUrlInput());
        if (!$asin) {
            return;
        }
        $key = 'pgamzn' . $asin;
        if ($this->cache->contains($key)) {
            $item = $this->cache->fetch($key);
        } else {
            $item = $this->amazon()->itemLookup($asin, [
                'ResponseGroup' => 'Large,ItemAttributes,Reviews',                 'AssociateTag' => get_option(Settings::AMAZON_ASSOCIATE_TAG)
            ]);
            unset($item->BrowseNodes);
            $this->cache->save($key, $item, $this->lifetime);
            sleep($this->sleepSecondsAfterEachCall);
        }
        if (!empty($item->Title)) {
            $p->addTitle($item->Title, self::SITE);
        }
        $p->addUrl($item->DetailPageURL, self::SITE);
        $p->setUpc($item->UPC);
        $p->setEan($item->EAN);
        if (isset($item->EditorialReviews) && count($item->EditorialReviews)) {
            $p->addDescription(array_shift($item->EditorialReviews)->Content, self::SITE);
        }
        if ($item->MediumImage) {
            $p->addImage($item->MediumImage->Url, null, null, self::SITE);
        }
        if (!empty($item->Amount) && !empty($item->FormattedPrice)) {
            $p->addPrice($item->Amount / 100, substr($item->FormattedPrice, 0, 1), self::SITE);
        }
    }
    public function amazon()
    {
        $appId = get_option(Settings::AMAZON_APP_ID);
        $cc = get_option(Settings::AMAZON_CC);
        $secret = get_option(Settings::AMAZON_SECRET_KEY);
        if (!$appId || !$cc || !$secret || !get_option(Settings::AMAZON_ASSOCIATE_TAG)) {
            throw new \InvalidArgumentException('Missing amazon settings');
        }
        return new Amazon(
            $appId,
            $cc,
            $secret
        );
    }
}
