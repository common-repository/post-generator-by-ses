<?php
namespace DTS\eBaySDK\Types;
use \DTS\eBaySDK\Exceptions;
use \DTS\eBaySDK\JmesPath\JmesPathableArrayInterface;
class RepeatableType implements \ArrayAccess, \Countable, \Iterator, JmesPathableArrayInterface
{
    private $data = [];
    private $position = 0;
    private $class;
    private $property;
    private $expectedType;
    public function __construct($class, $property, $expectedType)
    {
        $this->class = $class;
        $this->property = $property;
        $this->expectedType = $expectedType;
    }
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }
    public function offsetSet($offset, $value)
    {
        self::ensurePropertyType($value);
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
    public function count()
    {
        return count($this->data);
    }
    public function current()
    {
        return $this->offsetGet($this->position);
    }
    public function key()
    {
        return $this->position;
    }
    public function next()
    {
        $this->position++;
    }
    public function rewind()
    {
        $this->position = 0;
    }
    public function valid()
    {
        return $this->offsetExists($this->position);
    }
    private function ensurePropertyType($value)
    {
        $actualType = gettype($value);
        if ('object' === $actualType) {
            $actualType = get_class($value);
        }
        $valid = explode('|', $this->expectedType);
        $isValid = false;
        foreach ($valid as $check) {
            if ($check !== 'any' && \DTS\eBaySDK\checkPropertyType($check)) {
                if ($check === $actualType) {
                    return;
                }
                $isValid = false;
            } else {
                $isValid = true;
            }
        }
        if (!$isValid) {
            throw new Exceptions\InvalidPropertyTypeException($this->property, $this->expectedType, $actualType);
        }
    }
}
