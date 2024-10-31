<?php
namespace DTS\eBaySDK\Types;
use \DTS\eBaySDK\Types;
use \DTS\eBaySDK\Exceptions;
use \DTS\eBaySDK\JmesPath\Env;
use \DTS\eBaySDK\JmesPath\JmesPathableObjectInterface;
class BaseType implements JmesPathableObjectInterface
{
    protected static $properties = [];
    protected static $xmlNamespaces = [];
    protected static $requestXmlRootElementNames = [];
    private $values = [];
    private $attachment;
    public function __construct(array $values = [])
    {
        if (!array_key_exists(__CLASS__, self::$properties)) {
            self::$properties[__CLASS__] = [];
        }
        $this->setValues(__CLASS__, $values);
        $this->attachment = ['data' => null, 'mimeType' => null];
    }
    public function __get($name)
    {
        return $this->get(get_class($this), $name);
    }
    public function __set($name, $value)
    {
        $this->set(get_class($this), $name, $value);
    }
    public function __isset($name)
    {
        return $this->isPropertySet(get_class($this), $name);
    }
    public function __unset($name)
    {
        $this->unSetProperty(get_class($this), $name);
    }
    public function toRequestXml()
    {
        return $this->toXml(self::$requestXmlRootElementNames[get_class($this)], true);
    }
    private function toXml($elementName, $rootElement = false)
    {
        return sprintf(
            '%s<%s%s%s>%s</%s>',
            $rootElement ? "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" : '',
            $elementName,
            $this->attributesToXml(),
            array_key_exists(get_class($this), self::$xmlNamespaces) ? sprintf(' %s', self::$xmlNamespaces[get_class($this)]) : '',
            $this->propertiesToXml(),
            $elementName
        );
    }
    public function elementMeta($elementName)
    {
        $class = get_class($this);
        if (array_key_exists($elementName, self::$properties[$class])) {
            $info = self::$properties[$class][$elementName];
            $nameKey = $info['attribute'] ? 'attributeName' : 'elementName';
            if (array_key_exists($nameKey, $info)) {
                if ($info[$nameKey] === $elementName) {
                    $meta = new \stdClass();
                    $meta->propertyName = $elementName;
                    $meta->phpType = $info['type'];
                    $meta->repeatable = $info['repeatable'];
                    $meta->attribute = $info['attribute'];
                    $meta->elementName = $info[$nameKey];
                    $meta->strData = '';
                    return $meta;
                }
            }
        }
        return null;
    }
    public function attachment($data = null, $mimeType = 'application/octet-stream')
    {
        if ($data !== null) {
            if (is_array($data)) {
                $this->attachment['data'] = array_key_exists('data', $data) ? $data['data'] : null;
                $this->attachment['mimeType'] = array_key_exists('mimeType', $data) ? $data['mimeType'] : 'application/octet-stream';
            } else {
                $this->attachment['data'] = $data;
                $this->attachment['mimeType'] = $mimeType;
            }
        }
        return $this->attachment;
    }
    public function hasAttachment()
    {
        return $this->attachment['data'] !== null;
    }
    public function toArray()
    {
        $array = [];
        foreach (self::$properties[get_class($this)] as $name => $info) {
            if (!array_key_exists($name, $this->values)) {
                continue;
            }
            $value = $this->values[$name];
            if ($info['repeatable']) {
                if (count($value)) {
                    $array[$name] = [];
                    foreach ($value as $property) {
                        $array[$name][] = self::propertyToArrayValue($property);
                    }
                }
            } else {
                $array[$name] = self::propertyToArrayValue($value);
            }
        }
        return $array;
    }
    public function search($expression)
    {
        return Env::search($expression, $this);
    }
    public function __toString()
    {
        return json_encode($this->toArray());
    }
    protected function setValues($class, array $values = [])
    {
        foreach ($values as $property => $value) {
            $value = self::removeNull($value);
            if (!is_null($value)) {
                $actualValue = self::determineActualValueToAssign($class, $property, $value);
                $this->set($class, $property, $actualValue);
            }
        }
    }
    private function get($class, $name)
    {
        self::ensurePropertyExists($class, $name);
        return $this->getValue($class, $name);
    }
    private function set($class, $name, $value)
    {
        self::ensurePropertyExists($class, $name);
        self::ensurePropertyType($class, $name, $value);
        $this->setValue($class, $name, $value);
    }
    private function isPropertySet($class, $name)
    {
        self::ensurePropertyExists($class, $name);
        return array_key_exists($name, $this->values);
    }
    private function unSetProperty($class, $name)
    {
        self::ensurePropertyExists($class, $name);
        unset($this->values[$name]);
    }
    private function getValue($class, $name)
    {
        $info = self::propertyInfo($class, $name);
        if ($info['repeatable'] && !array_key_exists($name, $this->values)) {
            $this->values[$name] = new Types\RepeatableType($class, $name, $info['type']);
        }
        return array_key_exists($name, $this->values) ? $this->values[$name] : null;
    }
    private function setValue($class, $name, $value)
    {
        $info = self::propertyInfo($class, $name);
        if (!$info['repeatable']) {
            $this->values[$name] = $value;
        } else {
            $actualType = self::getActualType($value);
            if ('array' !== $actualType) {
                throw new Exceptions\InvalidPropertyTypeException($name, 'DTS\eBaySDK\Types\RepeatableType', $actualType);
            } else {
                $this->values[$name] = new Types\RepeatableType(get_class($this), $name, $info['type']);
                foreach ($value as $item) {
                    $this->values[$name][] = $item;
                }
            }
        }
    }
    private function attributesToXml()
    {
        $attributes = [];
        foreach (self::$properties[get_class($this)] as $name => $info) {
            if (!$info['attribute']) {
                continue;
            }
            if (!array_key_exists($name, $this->values)) {
                continue;
            }
            $attributes[] = self::attributeToXml($info['attributeName'], $this->values[$name]);
        }
        return join('', $attributes);
    }
    private function propertiesToXml()
    {
        $properties = [];
        foreach (self::$properties[get_class($this)] as $name => $info) {
            if ($info['attribute']) {
                continue;
            }
            if (!array_key_exists($name, $this->values)) {
                continue;
            }
            $value = $this->values[$name];
            if (!array_key_exists('elementName', $info) && !array_key_exists('attributeName', $info)) {
                $properties[] = self::encodeValueXml($value);
            } else {
                if ($info['repeatable']) {
                    foreach ($value as $property) {
                        $properties[] = self::propertyToXml($info['elementName'], $property);
                    }
                } else {
                    $properties[] = self::propertyToXml($info['elementName'], $value);
                }
            }
        }
        return join("\n", $properties);
    }
    private static function ensurePropertyExists($class, $name)
    {
        if (!array_key_exists($name, self::$properties[$class])) {
            throw new Exceptions\UnknownPropertyException($name);
        }
    }
    private static function ensurePropertyType($class, $name, $value)
    {
        $isValid = false;
        $info = self::propertyInfo($class, $name);
        $actualType = self::getActualType($value);
        $valid = explode('|', $info['type']);
        foreach ($valid as $check) {
            if ($check !== 'any' && \DTS\eBaySDK\checkPropertyType($check)) {
                if ($check === $actualType || 'array' === $actualType) {
                    return;
                }
                $isValid = false;
            } else {
                $isValid = true;
            }
        }
        if (!$isValid) {
            $expectedType = $info['type'];
            throw new Exceptions\InvalidPropertyTypeException($name, $expectedType, $actualType);
        }
    }
    private static function getActualType($value)
    {
        $actualType = gettype($value);
        if ('object' === $actualType) {
            $actualType = get_class($value);
        }
        return $actualType;
    }
    private static function propertyInfo($class, $name)
    {
        return self::$properties[$class][$name];
    }
    protected static function getParentValues(array $properties, array $values)
    {
        return [
            array_diff_key($values, $properties),
            array_intersect_key($values, $properties)
        ];
    }
    private static function attributeToXml($name, $value)
    {
        return sprintf(' %s="%s"', $name, self::encodeValueXml($value));
    }
    private static function propertyToXml($name, $value)
    {
        if (is_subclass_of($value, '\DTS\eBaySDK\Types\BaseType', false)) {
            return $value->toXml($name);
        } else {
            return sprintf('<%s>%s</%s>', $name, self::encodeValueXml($value), $name);
        }
    }
    private static function encodeValueXml($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d\TH:i:s.000\Z');
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } else {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', true);
        }
    }
    private static function propertyToArrayValue($value)
    {
        if (is_subclass_of($value, '\DTS\eBaySDK\Types\BaseType', false)) {
            return $value->toArray();
        } elseif ($value instanceof \DateTime) {
            return $value->format('Y-m-d\TH:i:s.000\Z');
        } else {
            return $value;
        }
    }
    private static function determineActualValueToAssign($class, $property, $value)
    {
        if (!array_key_exists($property, self::$properties[$class])) {
            return $value;
        }
        $info = self::propertyInfo($class, $property);
        if ($info['repeatable'] && is_array($value)) {
            $values = [];
            foreach ($value as $val) {
                $values[] = self::actualValue($info, $val);
            }
            return $values;
        }
        return self::actualValue($info, $value);
    }
    private static function actualValue(array $info, $value)
    {
        if (is_object($value)) {
            return $value;
        }
        $types = explode('|', $info['type']);
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
        return new $info['type']($value);
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
}
