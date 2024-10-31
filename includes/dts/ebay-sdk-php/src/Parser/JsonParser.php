<?php
namespace DTS\eBaySDK\Parser;
class JsonParser
{
    public static function parseAndAssignProperties($object, $json)
    {
        $properties = $json !== '' ? json_decode($json, true) : [];
        self::assignProperties($object, $properties);
    }
    private static function assignProperties(\DTS\eBaySDK\Types\BaseType $object, array $properties)
    {
        foreach ($properties as $property => $value) {
            $propertyMeta = $object->elementMeta($property);
                                                if ($propertyMeta) {
                $value = self::removeNull($value);
                if (!is_null($value)) {
                    $actualValue = self::determineActualValueToAssign($propertyMeta, $value);
                    $object->{$propertyMeta->propertyName} = $actualValue;
                }
            }
        }
    }
    private static function removeNull($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        return array_filter($value, function ($val) {
            return !is_null($val);
        });
    }
    private static function determineActualValueToAssign($propertyMeta, $value)
    {
        if ($propertyMeta->repeatable && is_array($value)) {
            $values = [];
            foreach ($value as $val) {
                $values[] = self::actualValue($propertyMeta, $val);
            }
            return $values;
        }
        return self::actualValue($propertyMeta, $value);
    }
    private static function actualValue(\stdClass $propertyMeta, $value)
    {
        if (is_object($value)) {
            return $value;
        }
        $types = explode('|', $propertyMeta->phpType);
        foreach ($types as $type) {
            switch ($type) {
                case 'integer':
                case 'string':
                case 'double':
                case 'boolean':
                case 'any':
                    return $value;
                case 'DateTime':
                    return new \DateTime($value, new \DateTimeZone('UTC'));
            }
        }
        $object = new $propertyMeta->phpType();
        self::assignProperties($object, $value);
        return $object;
    }
}
