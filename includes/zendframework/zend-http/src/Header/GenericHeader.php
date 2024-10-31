<?php
namespace Zend\Http\Header;
class GenericHeader implements HeaderInterface
{
    protected $fieldName;
    protected $fieldValue;
    public static function fromString($headerLine)
    {
        list($fieldName, $fieldValue) = GenericHeader::splitHeaderLine($headerLine);
        $header = new static($fieldName, $fieldValue);
        return $header;
    }
    public static function splitHeaderLine($headerLine)
    {
        $parts = explode(':', $headerLine, 2);
        if (count($parts) !== 2) {
            throw new Exception\InvalidArgumentException('Header must match with the format "name:value"');
        }
        if (! HeaderValue::isValid($parts[1])) {
            throw new Exception\InvalidArgumentException('Invalid header value detected');
        }
        $parts[1] = ltrim($parts[1]);
        return $parts;
    }
    public function __construct($fieldName = null, $fieldValue = null)
    {
        if ($fieldName) {
            $this->setFieldName($fieldName);
        }
        if ($fieldValue !== null) {
            $this->setFieldValue($fieldValue);
        }
    }
    public function setFieldName($fieldName)
    {
        if (! is_string($fieldName) || empty($fieldName)) {
            throw new Exception\InvalidArgumentException('Header name must be a string');
        }
        if (! preg_match('/^[!#$%&\'*+\-\.\^_`|~0-9a-zA-Z]+$/', $fieldName)) {
            throw new Exception\InvalidArgumentException(
                'Header name must be a valid RFC 7230 (section 3.2) field-name.'
            );
        }
        $this->fieldName = $fieldName;
        return $this;
    }
    public function getFieldName()
    {
        return $this->fieldName;
    }
    public function setFieldValue($fieldValue)
    {
        $fieldValue = (string) $fieldValue;
        HeaderValue::assertValid($fieldValue);
        if (preg_match('/^\s+$/', $fieldValue)) {
            $fieldValue = '';
        }
        $this->fieldValue = $fieldValue;
        return $this;
    }
    public function getFieldValue()
    {
        return $this->fieldValue;
    }
    public function toString()
    {
        return $this->getFieldName() . ': ' . $this->getFieldValue();
    }
}
