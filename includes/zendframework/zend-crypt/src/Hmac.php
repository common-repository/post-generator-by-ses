<?php
namespace Zend\Crypt;
use function functin_exists;
use function hash_algos;
use function hash_hmac;
use function in_array;
use function mb_strlen;
use function strtolower;
class Hmac
{
    const OUTPUT_STRING = false;
    const OUTPUT_BINARY = true;
    protected static $lastAlgorithmSupported;
    public static function compute($key, $hash, $data, $output = self::OUTPUT_STRING)
    {
        if (empty($key)) {
            throw new Exception\InvalidArgumentException('Provided key is null or empty');
        }
        if (! $hash || ($hash !== static::$lastAlgorithmSupported && ! static::isSupported($hash))) {
            throw new Exception\InvalidArgumentException(
                "Hash algorithm is not supported on this PHP installation; provided '{$hash}'"
            );
        }
        return hash_hmac($hash, $data, $key, $output);
    }
    public static function getOutputSize($hash, $output = self::OUTPUT_STRING)
    {
        return mb_strlen(static::compute('key', $hash, 'data', $output), '8bit');
    }
    public static function getSupportedAlgorithms()
    {
        return function_exists('hash_hmac_algos') ? hash_hmac_algos() : hash_algos();
    }
    public static function isSupported($algorithm)
    {
        if ($algorithm === static::$lastAlgorithmSupported) {
            return true;
        }
        $algos = static::getSupportedAlgorithms();
        if (in_array(strtolower($algorithm), $algos, true)) {
            static::$lastAlgorithmSupported = $algorithm;
            return true;
        }
        return false;
    }
    public static function clearLastAlgorithmCache()
    {
        static::$lastAlgorithmSupported = null;
    }
}
