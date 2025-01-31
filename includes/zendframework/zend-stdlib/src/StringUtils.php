<?php
namespace Zend\Stdlib;
use Zend\Stdlib\StringWrapper\StringWrapperInterface;
abstract class StringUtils
{
    protected static $wrapperRegistry = null;
    protected static $singleByteEncodings = [
        'ASCII', '7BIT', '8BIT',
        'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5',
        'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10',
        'ISO-8859-11', 'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16',
        'CP-1251', 'CP-1252',
            ];
    protected static $hasPcreUnicodeSupport = null;
    public static function getRegisteredWrappers()
    {
        if (static::$wrapperRegistry === null) {
            static::$wrapperRegistry = [];
            if (extension_loaded('intl')) {
                static::$wrapperRegistry[] = 'Zend\Stdlib\StringWrapper\Intl';
            }
            if (extension_loaded('mbstring')) {
                static::$wrapperRegistry[] = 'Zend\Stdlib\StringWrapper\MbString';
            }
            if (extension_loaded('iconv')) {
                static::$wrapperRegistry[] = 'Zend\Stdlib\StringWrapper\Iconv';
            }
            static::$wrapperRegistry[] = 'Zend\Stdlib\StringWrapper\Native';
        }
        return static::$wrapperRegistry;
    }
    public static function registerWrapper($wrapper)
    {
        $wrapper = (string) $wrapper;
        if (! in_array($wrapper, static::$wrapperRegistry, true)) {
            static::$wrapperRegistry[] = $wrapper;
        }
    }
    public static function unregisterWrapper($wrapper)
    {
        $index = array_search((string) $wrapper, static::$wrapperRegistry, true);
        if ($index !== false) {
            unset(static::$wrapperRegistry[$index]);
        }
    }
    public static function resetRegisteredWrappers()
    {
        static::$wrapperRegistry = null;
    }
    public static function getWrapper($encoding = 'UTF-8', $convertEncoding = null)
    {
        foreach (static::getRegisteredWrappers() as $wrapperClass) {
            if ($wrapperClass::isSupported($encoding, $convertEncoding)) {
                $wrapper = new $wrapperClass($encoding, $convertEncoding);
                $wrapper->setEncoding($encoding, $convertEncoding);
                return $wrapper;
            }
        }
        throw new Exception\RuntimeException(
            'No wrapper found supporting "' . $encoding . '"'
            . (($convertEncoding !== null) ? ' and "' . $convertEncoding . '"' : '')
        );
    }
    public static function getSingleByteEncodings()
    {
        return static::$singleByteEncodings;
    }
    public static function isSingleByteEncoding($encoding)
    {
        return in_array(strtoupper($encoding), static::$singleByteEncodings);
    }
    public static function isValidUtf8($str)
    {
        return is_string($str) && ($str === '' || preg_match('/^./su', $str) == 1);
    }
    public static function hasPcreUnicodeSupport()
    {
        if (static::$hasPcreUnicodeSupport === null) {
            ErrorHandler::start();
            static::$hasPcreUnicodeSupport = defined('PREG_BAD_UTF8_OFFSET_ERROR') && preg_match('/\pL/u', 'a') == 1;
            ErrorHandler::stop();
        }
        return static::$hasPcreUnicodeSupport;
    }
}
