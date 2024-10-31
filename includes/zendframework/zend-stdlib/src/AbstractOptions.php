<?php
namespace Zend\Stdlib;
use Traversable;
abstract class AbstractOptions implements ParameterObjectInterface
{
    protected $__strictMode__ = true;
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setFromArray($options);
        }
    }
    public function setFromArray($options)
    {
        if ($options instanceof self) {
            $options = $options->toArray();
        }
        if (! is_array($options) && ! $options instanceof Traversable) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Parameter provided to %s must be an %s, %s or %s',
                    __METHOD__,
                    'array',
                    'Traversable',
                    'Zend\Stdlib\AbstractOptions'
                )
            );
        }
        foreach ($options as $key => $value) {
            $this->__set($key, $value);
        }
        return $this;
    }
    public function toArray()
    {
        $array = [];
        $transform = function ($letters) {
            $letter = array_shift($letters);
            return '_' . strtolower($letter);
        };
        foreach ($this as $key => $value) {
            if ($key === '__strictMode__') {
                continue;
            }
            $normalizedKey = preg_replace_callback('/([A-Z])/', $transform, $key);
            $array[$normalizedKey] = $value;
        }
        return $array;
    }
    public function __set($key, $value)
    {
        $setter = 'set' . str_replace('_', '', $key);
        if (is_callable([$this, $setter])) {
            $this->{$setter}($value);
            return;
        }
        if ($this->__strictMode__) {
            throw new Exception\BadMethodCallException(sprintf(
                'The option "%s" does not have a callable "%s" ("%s") setter method which must be defined',
                $key,
                'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))),
                $setter
            ));
        }
    }
    public function __get($key)
    {
        $getter = 'get' . str_replace('_', '', $key);
        if (is_callable([$this, $getter])) {
            return $this->{$getter}();
        }
        throw new Exception\BadMethodCallException(sprintf(
            'The option "%s" does not have a callable "%s" getter method which must be defined',
            $key,
            'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)))
        ));
    }
    public function __isset($key)
    {
        $getter = 'get' . str_replace('_', '', $key);
        return method_exists($this, $getter) && null !== $this->__get($key);
    }
    public function __unset($key)
    {
        try {
            $this->__set($key, null);
        } catch (Exception\BadMethodCallException $e) {
            throw new Exception\InvalidArgumentException(
                'The class property $' . $key . ' cannot be unset as'
                . ' NULL is an invalid value for it',
                0,
                $e
            );
        }
    }
}
