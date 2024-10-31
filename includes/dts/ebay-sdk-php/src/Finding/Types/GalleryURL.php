<?php
namespace DTS\eBaySDK\Finding\Types;
class GalleryURL extends \DTS\eBaySDK\Types\URIType
{
    private static $propertyTypes = [
        'gallerySize' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => true,
            'attributeName' => 'gallerySize'
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
