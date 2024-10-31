<?php
namespace DTS\eBaySDK\Finding\Types;
class FindItemsByProductRequest extends \DTS\eBaySDK\Finding\Types\BaseFindingServiceRequest
{
    private static $propertyTypes = [
        'productId' => [
            'type' => 'DTS\eBaySDK\Finding\Types\ProductId',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'productId'
        ],
        'itemFilter' => [
            'type' => 'DTS\eBaySDK\Finding\Types\ItemFilter',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'itemFilter'
        ],
        'outputSelector' => [
            'type' => 'string',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'outputSelector'
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
        if (!array_key_exists(__CLASS__, self::$requestXmlRootElementNames)) {
            self::$requestXmlRootElementNames[__CLASS__] = 'findItemsByProductRequest';
        }
        $this->setValues(__CLASS__, $childValues);
    }
}
