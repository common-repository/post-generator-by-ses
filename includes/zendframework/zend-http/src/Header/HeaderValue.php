<?php
namespace Zend\Http\Header;
final class HeaderValue
{
    private function __construct()
    {
    }
    public static function filter($value)
    {
        $value  = (string) $value;
        $length = strlen($value);
        $string = '';
        for ($i = 0; $i < $length; $i += 1) {
            $ascii = ord($value[$i]);
                                                                        if (($ascii < 32 && $ascii !== 9)
                || $ascii === 127
                || $ascii > 254
            ) {
                continue;
            }
            $string .= $value[$i];
        }
        return $string;
    }
    public static function isValid($value)
    {
        $value  = (string) $value;
        $length = strlen($value);
        for ($i = 0; $i < $length; $i += 1) {
            $ascii = ord($value[$i]);
                                                                        if (($ascii < 32 && $ascii !== 9)
                || $ascii === 127
                || $ascii > 254
            ) {
                return false;
            }
        }
        return true;
    }
    public static function assertValid($value)
    {
        if (! self::isValid($value)) {
            throw new Exception\InvalidArgumentException('Invalid header value');
        }
    }
}
