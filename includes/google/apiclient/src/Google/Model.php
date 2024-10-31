<?php
class Google_Model implements ArrayAccess
{
    const NULL_VALUE = '{}gapi-php-null';
    protected $internal_gapi_mappings = [];
    protected $modelData = [];
    protected $processed = [];
    final public function __construct()
    {
        if (func_num_args() == 1 && is_array(func_get_arg(0))) {
            $array = func_get_arg(0);
            $this->mapTypes($array);
        }
        $this->gapiInit();
    }
    public function __get($key)
    {
        $keyType = $this->keyType($key);
        $keyDataType = $this->dataType($key);
        if ($keyType && !isset($this->processed[$key])) {
            if (isset($this->modelData[$key])) {
                $val = $this->modelData[$key];
            } elseif ($keyDataType == 'array' || $keyDataType == 'map') {
                $val = [];
            } else {
                $val = null;
            }
            if ($this->isAssociativeArray($val)) {
                if ($keyDataType && 'map' == $keyDataType) {
                    foreach ($val as $arrayKey => $arrayItem) {
                        $this->modelData[$key][$arrayKey] =
                new $keyType($arrayItem);
                    }
                } else {
                    $this->modelData[$key] = new $keyType($val);
                }
            } elseif (is_array($val)) {
                $arrayObject = [];
                foreach ($val as $arrayIndex => $arrayItem) {
                    $arrayObject[$arrayIndex] = new $keyType($arrayItem);
                }
                $this->modelData[$key] = $arrayObject;
            }
            $this->processed[$key] = true;
        }
        return isset($this->modelData[$key]) ? $this->modelData[$key] : null;
    }
    protected function mapTypes($array)
    {
        foreach ($array as $key => $val) {
            if ($keyType = $this->keyType($key)) {
                $dataType = $this->dataType($key);
                if ($dataType == 'array' || $dataType == 'map') {
                    $this->$key = [];
                    foreach ($val as $itemKey => $itemVal) {
                        if ($itemVal instanceof $keyType) {
                            $this->{$key}[$itemKey] = $itemVal;
                        } else {
                            $this->{$key}[$itemKey] = new $keyType($itemVal);
                        }
                    }
                } elseif ($val instanceof $keyType) {
                    $this->$key = $val;
                } else {
                    $this->$key = new $keyType($val);
                }
                unset($array[$key]);
            } elseif (property_exists($this, $key)) {
                $this->$key = $val;
                unset($array[$key]);
            } elseif (property_exists($this, $camelKey = $this->camelCase($key))) {
                $this->$camelKey = $val;
            }
        }
        $this->modelData = $array;
    }
    protected function gapiInit()
    {
        return;
    }
    public function toSimpleObject()
    {
        $object = new stdClass();
        foreach ($this->modelData as $key => $val) {
            $result = $this->getSimpleValue($val);
            if ($result !== null) {
                $object->$key = $this->nullPlaceholderCheck($result);
            }
        }
        $reflect = new ReflectionObject($this);
        $props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($props as $member) {
            $name = $member->getName();
            $result = $this->getSimpleValue($this->$name);
            if ($result !== null) {
                $name = $this->getMappedName($name);
                $object->$name = $this->nullPlaceholderCheck($result);
            }
        }
        return $object;
    }
    private function getSimpleValue($value)
    {
        if ($value instanceof Google_Model) {
            return $value->toSimpleObject();
        } elseif (is_array($value)) {
            $return = [];
            foreach ($value as $key => $a_value) {
                $a_value = $this->getSimpleValue($a_value);
                if ($a_value !== null) {
                    $key = $this->getMappedName($key);
                    $return[$key] = $this->nullPlaceholderCheck($a_value);
                }
            }
            return $return;
        }
        return $value;
    }
    private function nullPlaceholderCheck($value)
    {
        if ($value === self::NULL_VALUE) {
            return null;
        }
        return $value;
    }
    private function getMappedName($key)
    {
        if (isset($this->internal_gapi_mappings, $this->internal_gapi_mappings[$key])) {
            $key = $this->internal_gapi_mappings[$key];
        }
        return $key;
    }
    protected function isAssociativeArray($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $keys = array_keys($array);
        foreach ($keys as $key) {
            if (is_string($key)) {
                return true;
            }
        }
        return false;
    }
    public function assertIsArray($obj, $method)
    {
        if ($obj && !is_array($obj)) {
            throw new Google_Exception(
          "Incorrect parameter type passed to $method(). Expected an array."
      );
        }
    }
    public function offsetExists($offset)
    {
        return isset($this->$offset) || isset($this->modelData[$offset]);
    }
    public function offsetGet($offset)
    {
        return isset($this->$offset) ?
        $this->$offset :
        $this->__get($offset);
    }
    public function offsetSet($offset, $value)
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        } else {
            $this->modelData[$offset] = $value;
            $this->processed[$offset] = true;
        }
    }
    public function offsetUnset($offset)
    {
        unset($this->modelData[$offset]);
    }
    protected function keyType($key)
    {
        $keyType = $key . 'Type';
        if (property_exists($this, $keyType) && class_exists($this->$keyType)) {
            return $this->$keyType;
        }
    }
    protected function dataType($key)
    {
        $dataType = $key . 'DataType';
        if (property_exists($this, $dataType)) {
            return $this->$dataType;
        }
    }
    public function __isset($key)
    {
        return isset($this->modelData[$key]);
    }
    public function __unset($key)
    {
        unset($this->modelData[$key]);
    }
    private function camelCase($value)
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        $value = str_replace(' ', '', $value);
        $value[0] = strtolower($value[0]);
        return $value;
    }
}
