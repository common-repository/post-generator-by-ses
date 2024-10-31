<?php
namespace DTS\eBaySDK\Finding\Types;
class BaseFindingServiceResponse extends \DTS\eBaySDK\Finding\Types\BaseServiceResponse
{
    private static $propertyTypes = [
        'searchResult' => [
            'type' => 'DTS\eBaySDK\Finding\Types\SearchResult',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'searchResult'
        ],
        'paginationOutput' => [
            'type' => 'DTS\eBaySDK\Finding\Types\PaginationOutput',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'paginationOutput'
        ],
        'itemSearchURL' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'itemSearchURL'
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
