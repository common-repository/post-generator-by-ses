<?php
namespace DTS\eBaySDK\Finding\Types;
class BestMatchFindingServiceRequest extends \DTS\eBaySDK\Finding\Types\BaseServiceRequest
{
    private static $propertyTypes = [
        'paginationInput' => [
            'type' => 'DTS\eBaySDK\Finding\Types\PaginationInput',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'paginationInput'
        ],
        'buyerPostalCode' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'buyerPostalCode'
        ],
        'affiliate' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Affiliate',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'affiliate'
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
