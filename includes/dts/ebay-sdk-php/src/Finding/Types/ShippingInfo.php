<?php
namespace DTS\eBaySDK\Finding\Types;
class ShippingInfo extends \DTS\eBaySDK\Types\BaseType
{
    private static $propertyTypes = [
        'shippingServiceCost' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Amount',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'shippingServiceCost'
        ],
        'shippingType' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'shippingType'
        ],
        'shipToLocations' => [
            'type' => 'string',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'shipToLocations'
        ],
        'expeditedShipping' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'expeditedShipping'
        ],
        'oneDayShippingAvailable' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'oneDayShippingAvailable'
        ],
        'handlingTime' => [
            'type' => 'integer',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'handlingTime'
        ],
        'intermediatedShipping' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'intermediatedShipping'
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
