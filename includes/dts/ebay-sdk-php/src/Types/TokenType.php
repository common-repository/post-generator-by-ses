<?php
namespace DTS\eBaySDK\Types;
class TokenType extends \DTS\eBaySDK\Types\BaseType
{
    private static $propertyTypes = [
        'value' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false
        ]
    ];
    public function __construct(array $values = [])
    {
        list($parentValues, $childValues) = self::getParentValues(self::$propertyTypes, $values);
        parent::__construct($parentValues);
        if (!array_key_exists(__CLASS__, self::$properties)) {
            self::$properties[__CLASS__] = array_merge(self::$properties[get_parent_class()], self::$propertyTypes);
        }
        $this->setValues(__CLASS__, $childValues);
    }
}
