<?php
namespace Zend\Http\Header;
use ArrayObject;
class Cookie extends ArrayObject implements HeaderInterface
{
    protected $encodeValue = true;
    public static function fromSetCookieArray(array $setCookies)
    {
        $nvPairs = [];
        foreach ($setCookies as $setCookie) {
            if (! $setCookie instanceof SetCookie) {
                throw new Exception\InvalidArgumentException(sprintf(
                    '%s requires an array of SetCookie objects',
                    __METHOD__
                ));
            }
            if (array_key_exists($setCookie->getName(), $nvPairs)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Two cookies with the same name were provided to %s',
                    __METHOD__
                ));
            }
            $nvPairs[$setCookie->getName()] = $setCookie->getValue();
        }
        return new static($nvPairs);
    }
    public static function fromString($headerLine)
    {
        $header = new static();
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
                if (strtolower($name) !== 'cookie') {
            throw new Exception\InvalidArgumentException('Invalid header line for Server string: "' . $name . '"');
        }
        $nvPairs = preg_split('#;\s*#', $value);
        $arrayInfo = [];
        foreach ($nvPairs as $nvPair) {
            $parts = explode('=', $nvPair, 2);
            if (count($parts) != 2) {
                throw new Exception\RuntimeException('Malformed Cookie header found');
            }
            list($name, $value) = $parts;
            $arrayInfo[$name] = urldecode($value);
        }
        $header->exchangeArray($arrayInfo);
        return $header;
    }
    public function __construct(array $array = [])
    {
        parent::__construct($array, ArrayObject::ARRAY_AS_PROPS);
    }
    public function setEncodeValue($encodeValue)
    {
        $this->encodeValue = (bool) $encodeValue;
        return $this;
    }
    public function getEncodeValue()
    {
        return $this->encodeValue;
    }
    public function getFieldName()
    {
        return 'Cookie';
    }
    public function getFieldValue()
    {
        $nvPairs = [];
        foreach ($this->flattenCookies($this) as $name => $value) {
            $nvPairs[] = $name . '=' . (($this->encodeValue) ? urlencode($value) : $value);
        }
        return implode('; ', $nvPairs);
    }
    protected function flattenCookies($data, $prefix = null)
    {
        $result = [];
        foreach ($data as $key => $value) {
            $key = $prefix ? $prefix . '[' . $key . ']' : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenCookies($value, $key));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    public function toString()
    {
        return 'Cookie: ' . $this->getFieldValue();
    }
    public function __toString()
    {
        return $this->toString();
    }
}
