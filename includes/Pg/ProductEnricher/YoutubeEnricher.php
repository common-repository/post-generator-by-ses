<?php
namespace Pg\ProductEnricher;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\VoidCache;
use Ec\Utils\StringUtils;
use Ec\Youtube\YoutubeService;
use Pg\Product;
use Pg\Settings;
class YoutubeEnricher implements ProductEnricherInterface
{
    const PRODUCT_TITLE_PLACEHOLDER = '%title%';
    const LIMIT_VIDEOS = 1;
    private $cache;
    private $lifetime;
    private $sleepSecondsAfterEachCall;
    public function __construct(Cache $cache)
    {
        $this->lifetime = get_option(Settings::GOOGLE_CACHE_LIFETIME);
        $this->cache = $this->lifetime ? $cache : new VoidCache();
        $this->sleepSecondsAfterEachCall = 0.1;
    }
    public function enrich(Product $p)
    {
        $titles = $p->getTitles();
        $q = $this->getYoutubeSearchString(array_shift($titles));
        $key = 'yt' . md5($q);
        if ($this->cache->contains($key)) {
            $items = $this->cache->fetch($key);
        } else {
            $items = $this->youtube()->searchVideo($q, self::LIMIT_VIDEOS);
            $this->cache->save($key, $items, $this->lifetime);
            sleep($this->sleepSecondsAfterEachCall);
        }
        if (empty($items)) {
            throw new \Exception('no results for ' . $q);
        }
        foreach ($items as $item) {
            $p->addYoutubeVideo($item->getId(), $item->getTitle(), $item->getDescription());
        }
    }
    public function youtube()
    {
        $an = get_option(Settings::GOOGLE_APP_NAME);
        $dk = get_option(Settings::GOOGLE_DEV_KEY);
        if (!$an || !$dk) {
            throw new \InvalidArgumentException('Youtube video not added. Api options missing');
        }
        $gc = new \Google_Client([
            'application_name' => $an,
            'developer_key' => $dk,
        ]);
        $gsyt = new \Google_Service_YouTube($gc);
        return new YoutubeService($gsyt);
    }
    private static function getYoutubeSearchString($productTitle)
    {
        $searchTemplate = get_option(Settings::YOUTUBE_SEARCH_STRING);
        if (strpos($searchTemplate, self::PRODUCT_TITLE_PLACEHOLDER) === false) {
            throw new \RuntimeException('Youtube search string setting does not contain ' . self::PRODUCT_TITLE_PLACEHOLDER . '. Skipped');
        }
        $productTitleClened = self::cleanProductTitle($productTitle);
        return str_replace(self::PRODUCT_TITLE_PLACEHOLDER, $productTitleClened, $searchTemplate);
    }
    private static function cleanProductTitle($productTitle)
    {
        if (strpos($productTitle, '(')) {
            $productTitle = strstr($productTitle, '(', true);
        }
        $productTitle = preg_replace('/[,.\-"\']/', ' ', $productTitle);
        $productTitle = preg_replace('!\s+!', ' ', $productTitle);
        $productTitle = StringUtils::shortenToMaxWords($productTitle, 6);
        return $productTitle;
    }
}
