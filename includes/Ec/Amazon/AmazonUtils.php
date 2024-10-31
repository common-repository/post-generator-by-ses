<?php
namespace Ec\Amazon;
use ZendService\Amazon\Item;
use ZendService\Amazon\OfferSet;
class AmazonUtils
{
    const ASIN_REGEXPR = 'B[0-9]{2}[0-9A-Z]{7}|[0-9]{9}(?:X|[0-9])';
    public static function replaceTag($link, $tag)
    {
        if (strpos($link, $tag) === false) {
            $link = preg_replace('/(tag=[^&]+)/', 'tag=' . $tag, $link);
        }
        return $link;
    }
    public static function isValidAsin($asin)
    {
        return preg_match('/' . self::ASIN_REGEXPR . '/i', $asin) ? true : false;
    }
    public static function getASINSFromTextLinesWithUrlsOrAsins($param, array $sep = [',', ' '])
    {
        $ret = [];
        $param = str_replace($sep, "\n", $param);
        $lines = explode("\n", $param);
        foreach ($lines as $urlOrAsin) {
            $ret[] = self::extractASINFromUrlOrText($urlOrAsin);
        }
        $ret = array_values(array_unique(array_filter(array_map('trim', $ret))));
        return $ret;
    }
    public static function extractASINFromUrlOrText($urlOrTextContainingAsin)
    {
        if (filter_var($urlOrTextContainingAsin, FILTER_VALIDATE_URL)
            && preg_match("/(?:dp|o|gp|product|-)\/(" . self::ASIN_REGEXPR . ')/', $urlOrTextContainingAsin, $matches)
            && isset($matches[1])
        ) {
            return $matches[1];
        }
        if (preg_match('#(' . self::ASIN_REGEXPR . ')#', $urlOrTextContainingAsin, $matches) && isset($matches[1])) {
            return $matches[1];
        }
        return;
    }
    public static function grabASINSfromHTML($html)
    {
        preg_match_all('/data-asin="(' . self::ASIN_REGEXPR . ')"/', $html, $matches);
        if (empty($matches[1])) {
            return [];
        }
        $ret = array_unique($matches[1]);
        sort($ret);
        return $ret;
    }
    public static function addAndOrderBySalesRankPosition(array $products)
    {
        $keyToRank = [];
        foreach ($products as $k => $product) {
            if (isset($product->SalesRank)) {
                $keyToRank[$k] = $product->SalesRank;
                $product->SalesRankPosition = 1;
            }
        }
        asort($keyToRank);
        $position = 1;
        foreach ($keyToRank as $k => $rank) {
            $products[$k]->SalesRankPosition = $position++;
        }
        usort($products, function ($a, $b) {
            if (empty($a->SalesRankPosition)) {
                return 1;
            }
            if (empty($b->SalesRankPosition)) {
                return 0;
            }
            return $a->SalesRankPosition > $b->SalesRankPosition ? 1 : 0;
        });
        return $products;
    }
    public static function shortenProductName($name, $discardCommaAndParAfter, $maxWords, $dots = ' ...')
    {
        $commaPos = strpos($name, ',');
        if ($commaPos > $discardCommaAndParAfter) {
            $name = substr($name, 0, $commaPos);
        }
        $parentPos = strpos($name, '(');
        if ($parentPos > $discardCommaAndParAfter) {
            $name = substr($name, 0, $parentPos);
        }
        $pieces = explode(' ', $name);
        $countTotalWords = count($pieces);
        $pieces = array_slice($pieces, 0, $maxWords);
        $name = join(' ', $pieces);
        if ($countTotalWords > $maxWords) {
            $name .= $dots;
        }
        $name = str_replace(['•'], '', $name);
        return $name;
    }
    public static function isErrorMessageAboutProductNotAvailable($string)
    {
        return strpos($string, 'not accessible through the Product') !== false
               || strpos($string, 'is not a valid value for ItemId') !== false
               || strpos($string, 'no results found') !== false;
    }
    public static function findCommonBrowseNodes(array $products)
    {
        $candidates = [];
        foreach ($products as $product) {
            foreach ($product->getNodes() as $node) {
                $amazonId = $node->getAmazonId();
                $candidates[$amazonId] = isset($candidates[$amazonId]) ? $candidates[$amazonId] + 1 : 1;
            }
        }
        arsort($candidates);
        return $candidates;
    }
    public static function findCommonRoots(array $titles)
    {
        $ret = trim(
            ucwords(
                self::longest_common_substring($titles)
            ),
            '-,. nav'
        );
        return $ret;
    }
    public static function findCommonPrefix(array $titles)
    {
        $prefix = array_shift($titles);
        $length = strlen($prefix);
        foreach ($titles as $w) {
            while ($length && substr($w, 0, $length) !== $prefix) {
                $length--;
                $prefix = substr($prefix, 0, -1);
            }
            if (!$length) {
                break;
            }
        }
        return trim($prefix);
    }
    private static function sortByStrlen($a, $b)
    {
        if (strlen($a) == strlen($b)) {
            return strcmp($a, $b);
        }
        return (strlen($a) < strlen($b)) ? -1 : 1;
    }
    private static function longest_common_substring($words)
    {
        $words = array_map('strtolower', array_map('trim', $words));
        usort($words, [__CLASS__, 'sortByStrlen']);
        $longest_common_substring = [];
        $shortest_string = str_split(array_shift($words));
        while (sizeof($shortest_string)) {
            array_unshift($longest_common_substring, '');
            foreach ($shortest_string as $ci => $char) {
                foreach ($words as $wi => $word) {
                    if (!strstr($word, $longest_common_substring[0] . $char)) {
                        break 2;
                    }
                }
                $longest_common_substring[0] .= $char;
            }
            array_shift($shortest_string);
        }
        usort($longest_common_substring, [__CLASS__, 'sortByStrlen']);
        return array_pop($longest_common_substring);
    }
    public static function findPriceRange(array $prices, $round = 0)
    {
        $prices = array_filter($prices);
        if (count($prices) < 2) {
            return [null, null];
        }
        $prices = array_map(function ($p) use ($round) {
            return round($p, $round);
        }, $prices);
        return [min($prices), max($prices)];
    }
    public static function browseNodeNameSeoFriendly($realname)
    {
        $seoname = $realname;
        $seoname = preg_replace('/\@/', ' at ', $seoname);
        $seoname = preg_replace('/\&/', ' and ', $seoname);
        $seoname = preg_replace('/\s[\s]+/', '-', $seoname);
        $seoname = preg_replace('/[\s\W]+/', '-', $seoname);
        $seoname = preg_replace('/^[\-]+/', '', $seoname);
        $seoname = preg_replace('/[\-]+$/', '', $seoname);
        $seoname = strtolower($seoname);
        return $seoname;
    }
    public static function replaceExpireDateInIframeUrl($url)
    {
        return preg_replace('/exp=\d{4}-\d{2}-\d{2}/', 'exp=' . (new \DateTime('tomorrow'))->format('Y-m-d'), $url);
    }
    public static function parseReportsOrderedItems($html)
    {
    }
    public static function countryCodeToBaseUrl($countryCode)
    {
        return 'https://www.amazon.'
               . self::countryCodeToSiteDomain($countryCode)
               . '/';
    }
    public static function countryCodeToSiteDomain($countryCode)
    {
        return [
            'BR' => 'com.br',
            'CA' => 'ca',
            'CN' => 'cn',
            'DE' => 'de',
            'ES' => 'es',
            'FR' => 'fr',
            'JP' => 'co.jp',
            'IN' => 'in',
            'IT' => 'it',
            'MX' => 'com.mx',
            'UK' => 'co.uk',
            'US' => 'com',
        ][$countryCode];
    }
    public static function getMarketPlaces()
    {
        return [
            'BR' => 'Brazil',
            'CA' => 'Canada',
            'CN' => 'China',
            'DE' => 'Germany',
            'ES' => 'Spain',
            'FR' => 'France',
            'JP' => 'Japan',
            'IN' => 'India',
            'IT' => 'Italy',
            'MX' => 'Mexico',
            'UK' => 'United Kindgom',
            'US' => 'United States',
        ];
    }
    public static function countryCodeToCountryName($cc)
    {
        return self::getMarketPlaces()[strtoupper($cc)];
    }
    public static function parsePrices($item)
    {
        $ret = [
            'listing' => [
                'amount' => null,
                'currency' => null,
                'discount' => null,
            ],
            'new' => [
                'amount' => null,
                'currency' => null,
                'quantity' => 0,
            ],
            'used' => [
                'amount' => null,
                'currency' => null,
                'quantity' => 0,
            ],
        ];
        if (isset($item->Amount)) {
            $ret['listing'] = [
                'amount' => $item->Amount,                 'currency' => $item->CurrencyCode,
                'discount' => null,
            ];
        }
        if (isset($item->Offers)) {
            $offerSet = $item->Offers;
            if (isset($offerSet->Offers)) {
                foreach ($offerSet->Offers as $offer) {
                    if (strtolower($offer->Condition) == 'new') {
                        if (isset($ret['new']['amount']) && $ret['new']['amount'] < $offer->Price) {
                            continue;
                        }
                        $ret['new'] = [
                            'amount' => $offer->Price,
                            'currency' => $offer->CurrencyCode,
                            'quantity' => $offer->Availability,
                        ];
                        if (!isset($item->Amount)) {
                            $ret['listing'] = [
                                'amount' => $offer->Price,
                                'currency' => $offer->CurrencyCode,
                            ];
                        }
                    }
                }
            }
            if ($offerSet instanceof OfferSet) {
                $ret['new'] = [
                    'amount' => $item->Offers->LowestNewPrice,
                    'currency' => $item->Offers->LowestNewPriceCurrency,
                    'quantity' => $item->Offers->TotalNew,
                ];
                $ret['used'] = [
                    'amount' => $item->Offers->LowestUsedPrice,
                    'currency' => $item->Offers->LowestUsedPriceCurrency,
                    'quantity' => $item->Offers->TotalUsed,
                ];
            }
            if (isset($item->Amount) & $ret['new']['amount'] < $ret['listing']['amount']) {
                $ret['listing']['discount'] = $ret['listing']['amount'] - $ret['new']['amount'];
                $ret['listing']['amount'] = $ret['new']['amount'];
            } else {
                $ret['listing']['discount'] = null;
            }
        }
        return $ret;
    }
    public static function xmlToAmazonItem($xmlString)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xmlString);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . '2011-08-01');
        $items = $xpath->query('//az:Items/az:Item');
        return new Item($items->item(0));
    }
}
