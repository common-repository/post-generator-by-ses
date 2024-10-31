<?php
namespace DTS\eBaySDK\Parser;
class XmlParser
{
    private $rootObjectClass;
    private $rootObject;
    private $metaStack;
    public function __construct($rootObjectClass)
    {
        $this->rootObjectClass = $rootObjectClass;
        $this->metaStack = new \SplStack();
    }
    public function parse($xml)
    {
        $parser = xml_parser_create_ns('UTF-8', '@');
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, 'startElement', 'endElement');
        xml_set_character_data_handler($parser, 'cdata');
        xml_parse($parser, $xml, true);
        xml_parser_free($parser);
        return $this->rootObject;
    }
    private function startElement($parser, $name, array $attributes)
    {
        $this->metaStack->push($this->getPhpMeta($this->normalizeElementName($name), $attributes));
    }
    private function cdata($parser, $cdata)
    {
        $this->metaStack->top()->strData .= $cdata;
    }
    private function endElement($parser, $name)
    {
        $meta = $this->metaStack->pop();
        if (!$this->metaStack->isEmpty()) {
                                                if ($meta->propertyName !== '') {
                $parentObject = $this->getParentObject();
                                if ($parentObject) {
                    if (!$meta->repeatable) {
                        $parentObject->{$meta->propertyName} = $this->getValueToAssign($meta);
                    } else {
                        $parentObject->{$meta->propertyName}[] = $this->getValueToAssign($meta);
                    }
                }
            }
        } else {
            $this->rootObject = $meta->phpObject;
        }
    }
    private function normalizeElementName($name)
    {
        $nsElement = explode('@', $name);
        if (count($nsElement) > 1) {
            array_shift($nsElement);
            return $nsElement[0];
        } else {
            return $name;
        }
    }
    private function getParentObject()
    {
        return $this->metaStack->top()->phpObject;
    }
    private function getPhpMeta($elementName, array $attributes)
    {
        $meta = new \stdClass();
        $meta->propertyName = '';
        $meta->phpType = '';
        $meta->repeatable = false;
        $meta->attribute = false;
        $meta->elementName = '';
        $meta->strData = '';
        if (!$this->metaStack->isEmpty()) {
            $parentObject = $this->getParentObject();
            if ($parentObject) {
                $elementMeta = $parentObject->elementMeta($elementName);
                if ($elementMeta) {
                    $meta = $elementMeta;
                }
            }
        } else {
            $meta->phpType = $this->rootObjectClass;
        }
        $meta->phpObject = $this->newPhpObject($meta);
        if ($meta->phpObject) {
            foreach ($attributes as $attribute => $value) {
                                if ('xmlns' === $attribute) {
                    continue;
                }
                $attributeMeta = $meta->phpObject->elementMeta($attribute);
                                                                if ($attributeMeta) {
                    $attributeMeta->strData = $value;
                    $meta->phpObject->{$attributeMeta->propertyName} = $this->getValueToAssignToProperty($attributeMeta);
                }
            }
        }
        return $meta;
    }
    private function newPhpObject(\stdClass $meta)
    {
        $phpTypes = explode('|', $meta->phpType);
        foreach ($phpTypes as $phpType) {
            switch ($phpType) {
                case 'integer':
                case 'string':
                case 'double':
                case 'boolean':
                case 'DateTime':
                    continue;
                default:
                    return $meta->phpType !== '' ? new $phpType() : null;
            }
        }
        return null;
    }
    private function getValueToAssign(\stdClass $meta)
    {
        if ($this->isSimplePhpType($meta)) {
            return $this->getValueToAssignToProperty($meta);
        } else {
            if ($this->setByValue($meta)) {
                $meta->phpObject->value = $this->getValueToAssignToValue($meta);
            }
            return $meta->phpObject;
        }
    }
    private function isSimplePhpType(\stdClass $meta)
    {
        $phpTypes = explode('|', $meta->phpType);
        foreach ($phpTypes as $phpType) {
            switch ($phpType) {
                case 'integer':
                case 'string':
                case 'double':
                case 'boolean':
                case 'DateTime':
                    continue;
                default:
                    return false;
            }
        }
        return true;
    }
    private function setByValue(\stdClass $meta)
    {
        return (
            is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\Base64BinaryType', false) ||
            is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\BooleanType', false) ||
            is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\DecimalType', false) ||
            is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\DoubleType', false) ||
            is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\IntegerType', false) ||
            is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\StringType', false) ||
            is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\TokenType', false) ||
            is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\URIType', false)
        );
    }
    private function getValueToAssignToProperty(\stdClass $meta)
    {
        switch ($meta->phpType) {
            case 'integer':
                return (integer)$meta->strData;
            case 'double':
                return (double)$meta->strData;
            case 'boolean':
                return strtolower($meta->strData) === 'true';
            case 'DateTime':
                return new \DateTime($meta->strData, new \DateTimeZone('UTC'));
            case 'string':
            default:
                return $meta->strData;
        }
    }
    private function getValueToAssignToValue(\stdClass $meta)
    {
        if (is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\Base64BinaryType', false)) {
            return $meta->strData;
        } elseif (is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\BooleanType', false)) {
            return strtolower($meta->strData) === 'true';
        } elseif (is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\DecimalType', false)) {
            return is_int(0 + $meta->strData) ? (integer)$meta->strData : (double)$meta->strData;
        } elseif (is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\DoubleType', false)) {
            return (double)$meta->strData;
        } elseif (is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\IntegerType', false)) {
            return (integer)$meta->strData;
        } elseif (is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\StringType', false)) {
            return $meta->strData;
        } elseif (is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\TokenType', false)) {
            return $meta->strData;
        } elseif (is_subclass_of($meta->phpObject, '\DTS\eBaySDK\Types\URIType', false)) {
            return $meta->strData;
        }
        return $meta->strData;
    }
}
