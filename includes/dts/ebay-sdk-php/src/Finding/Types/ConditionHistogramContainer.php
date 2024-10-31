<?php
namespace DTS\eBaySDK\Finding\Types;
class ConditionHistogramContainer extends \DTS\eBaySDK\Types\BaseType
{
    private static $propertyTypes = [
        'conditionHistogram' => [
            'type' => 'DTS\eBaySDK\Finding\Types\ConditionHistogram',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'conditionHistogram'
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
