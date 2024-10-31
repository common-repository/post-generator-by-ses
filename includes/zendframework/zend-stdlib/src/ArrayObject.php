<?php
namespace Zend\Stdlib;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use Serializable;
class ArrayObject implements IteratorAggregate, ArrayAccess, Serializable, Countable
{
    const STD_PROP_LIST = 1;
    const ARRAY_AS_PROPS = 2;
    protected $storage;
    protected $flag;
    protected $iteratorClass;
    protected $protectedProperties;
    public function __construct($input = [], $flags = self::STD_PROP_LIST, $iteratorClass = 'ArrayIterator')
    {
        $this->setFlags($flags);
        $this->storage = $input;
        $this->setIteratorClass($iteratorClass);
        $this->protectedProperties = array_keys(get_object_vars($this));
    }
    public function __isset($key)
    {
        if ($this->flag == self::ARRAY_AS_PROPS) {
            return $this->offsetExists($key);
        }
        if (in_array($key, $this->protectedProperties)) {
            throw new Exception\InvalidArgumentException('$key is a protected property, use a different key');
        }
        return isset($this->$key);
    }
    public function __set($key, $value)
    {
        if ($this->flag == self::ARRAY_AS_PROPS) {
            return $this->offsetSet($key, $value);
        }
        if (in_array($key, $this->protectedProperties)) {
            throw new Exception\InvalidArgumentException('$key is a protected property, use a different key');
        }
        $this->$key = $value;
    }
    public function __unset($key)
    {
        if ($this->flag == self::ARRAY_AS_PROPS) {
            return $this->offsetUnset($key);
        }
        if (in_array($key, $this->protectedProperties)) {
            throw new Exception\InvalidArgumentException('$key is a protected property, use a different key');
        }
        unset($this->$key);
    }
    public function &__get($key)
    {
        $ret = null;
        if ($this->flag == self::ARRAY_AS_PROPS) {
            $ret =& $this->offsetGet($key);
            return $ret;
        }
        if (in_array($key, $this->protectedProperties)) {
            throw new Exception\InvalidArgumentException('$key is a protected property, use a different key');
        }
        return $this->$key;
    }
    public function append($value)
    {
        $this->storage[] = $value;
    }
    public function asort()
    {
        asort($this->storage);
    }
    public function count()
    {
        return count($this->storage);
    }
    public function exchangeArray($data)
    {
        if (! is_array($data) && ! is_object($data)) {
            throw new Exception\InvalidArgumentException(
                'Passed variable is not an array or object, using empty array instead'
            );
        }
        if (is_object($data) && ($data instanceof self || $data instanceof \ArrayObject)) {
            $data = $data->getArrayCopy();
        }
        if (! is_array($data)) {
            $data = (array) $data;
        }
        $storage = $this->storage;
        $this->storage = $data;
        return $storage;
    }
    public function getArrayCopy()
    {
        return $this->storage;
    }
    public function getFlags()
    {
        return $this->flag;
    }
    public function getIterator()
    {
        $class = $this->iteratorClass;
        return new $class($this->storage);
    }
    public function getIteratorClass()
    {
        return $this->iteratorClass;
    }
    public function ksort()
    {
        ksort($this->storage);
    }
    public function natcasesort()
    {
        natcasesort($this->storage);
    }
    public function natsort()
    {
        natsort($this->storage);
    }
    public function offsetExists($key)
    {
        return isset($this->storage[$key]);
    }
    public function &offsetGet($key)
    {
        $ret = null;
        if (! $this->offsetExists($key)) {
            return $ret;
        }
        $ret =& $this->storage[$key];
        return $ret;
    }
    public function offsetSet($key, $value)
    {
        $this->storage[$key] = $value;
    }
    public function offsetUnset($key)
    {
        if ($this->offsetExists($key)) {
            unset($this->storage[$key]);
        }
    }
    public function serialize()
    {
        return serialize(get_object_vars($this));
    }
    public function setFlags($flags)
    {
        $this->flag = $flags;
    }
    public function setIteratorClass($class)
    {
        if (class_exists($class)) {
            $this->iteratorClass = $class;
            return ;
        }
        if (strpos($class, '\\') === 0) {
            $class = '\\' . $class;
            if (class_exists($class)) {
                $this->iteratorClass = $class;
                return ;
            }
        }
        throw new Exception\InvalidArgumentException('The iterator class does not exist');
    }
    public function uasort($function)
    {
        if (is_callable($function)) {
            uasort($this->storage, $function);
        }
    }
    public function uksort($function)
    {
        if (is_callable($function)) {
            uksort($this->storage, $function);
        }
    }
    public function unserialize($data)
    {
        $ar                        = unserialize($data);
        $this->protectedProperties = array_keys(get_object_vars($this));
        $this->setFlags($ar['flag']);
        $this->exchangeArray($ar['storage']);
        $this->setIteratorClass($ar['iteratorClass']);
        foreach ($ar as $k => $v) {
            switch ($k) {
                case 'flag':
                    $this->setFlags($v);
                    break;
                case 'storage':
                    $this->exchangeArray($v);
                    break;
                case 'iteratorClass':
                    $this->setIteratorClass($v);
                    break;
                case 'protectedProperties':
                    break;
                default:
                    $this->__set($k, $v);
            }
        }
    }
}
