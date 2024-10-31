<?php
namespace DTS\eBaySDK\Finding\Types;
class ListingInfo extends \DTS\eBaySDK\Types\BaseType
{
    private static $propertyTypes = [
        'bestOfferEnabled' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'bestOfferEnabled'
        ],
        'buyItNowAvailable' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'buyItNowAvailable'
        ],
        'buyItNowPrice' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Amount',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'buyItNowPrice'
        ],
        'convertedBuyItNowPrice' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Amount',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'convertedBuyItNowPrice'
        ],
        'startTime' => [
            'type' => 'DateTime',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'startTime'
        ],
        'endTime' => [
            'type' => 'DateTime',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'endTime'
        ],
        'listingType' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'listingType'
        ],
        'gift' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'gift'
        ]
    ];
    public function __construct(array $values = [])
    {
        list($parentValues, $childValues) = self::getParentValues(self::$propertyTypes, $values);
        parent::__construct($parentValues);
        if (!array_key_exists(__CLASS__, self::$properties)) {
            self::$properties[__CLASS__] = array_merge(self::$properties[get_parent_class()], self::$propertyTypes);
        }
        if (!array_key_exists(__CLASS__, self::$xmlNamespaces)) {
            self::$xmlNamespaces[__CLASS__] = 'xmlns="http://www.ebay.com/marketplace/search/v1/services"';
        }
        $this->setValues(__CLASS__, $childValues);
    }
}
