<?php
namespace Ec\Ebay;
class EbayUtils
{
    public static function getMinPricedItem(\Iterator $items)
    {
        $idToPrice = [];
        foreach ($items as $k => $item) {
            $idToPrice[$k] = $item->sellingStatus->currentPrice->value;
        }
        asort($idToPrice);
        reset($idToPrice);
        $firstKey = key($idToPrice);
        return $items[$firstKey];
    }
    public static function getMarketplaceIds()
    {
        return [
            'US' => 'EBAY-US',
            'CA' => 'EBAY-CA',
            'GB' => 'EBAY-GB',
            'AU' => 'EBAY-AU',
            'AT' => 'EBAY-AT',
            'BE' => 'EBAY-BE',
            'FR' => 'EBAY-FR',
            'DE' => 'EBAY-DE',
            'MOTORS' => 'EBAY-US.MOTORS',
            'IT' => 'EBAY-IT',
            'NL' => 'EBAY-NL',
            'ES' => 'EBAY-ES',
            'CH' => 'EBAY-CH',
            'HK' => 'EBAY-HK',
            'IN' => 'EBAY-IN',
            'IE' => 'EBAY-IE',
            'MY' => 'EBAY-MY',
            'PH' => 'EBAY-PH',
            'PL' => 'EBAY-PL',
            'SG' => 'EBAY-SG',
            'CN' => 'EBAY-CN'
        ];
    }
}
