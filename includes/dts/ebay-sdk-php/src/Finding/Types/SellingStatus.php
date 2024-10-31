<?php
namespace DTS\eBaySDK\Finding\Types;
class SellingStatus extends \DTS\eBaySDK\Types\BaseType
{
    private static $propertyTypes = [
        'currentPrice' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Amount',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'currentPrice'
        ],
        'convertedCurrentPrice' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Amount',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'convertedCurrentPrice'
        ],
        'bidCount' => [
            'type' => 'integer',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'bidCount'
        ],
        'sellingState' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'sellingState'
        ],
        'timeLeft' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'timeLeft'
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
