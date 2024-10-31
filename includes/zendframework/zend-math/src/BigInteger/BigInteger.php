<?php
namespace Zend\Math\BigInteger;
abstract class BigInteger
{
    protected static $defaultAdapter = null;
    public static function factory($adapterName = null)
    {
        if (null === $adapterName) {
            return static::getAvailableAdapter();
        }
        $adapterName = sprintf('%s\\Adapter\\%s', __NAMESPACE__, ucfirst($adapterName));
        if (! class_exists($adapterName)
            || ! is_subclass_of($adapterName, Adapter\AdapterInterface::class)
        ) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The adapter %s either does not exist or does not implement %s',
                $adapterName,
                Adapter\AdapterInterface::class
            ));
        }
        return new $adapterName();
    }
    public static function setDefaultAdapter($adapter)
    {
        static::$defaultAdapter = static::factory($adapter);
    }
    public static function getDefaultAdapter()
    {
        if (null === static::$defaultAdapter) {
            static::$defaultAdapter = static::getAvailableAdapter();
        }
        return static::$defaultAdapter;
    }
    public static function getAvailableAdapter()
    {
        if (extension_loaded('gmp')) {
            return static::factory('Gmp');
        }
        if (extension_loaded('bcmath')) {
            return static::factory('Bcmath');
        }
        throw new Exception\RuntimeException('Big integer math support is not detected');
    }
    public static function __callStatic($method, $args)
    {
        $adapter = static::getDefaultAdapter();
        return call_user_func_array([$adapter, $method], $args);
    }
}
