<?php
namespace Zend\Http\Header;
class ContentEncoding implements HeaderInterface
{
    protected $value;
    public static function fromString($headerLine)
    {
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
                if (strtolower($name) !== 'content-encoding') {
            throw new Exception\InvalidArgumentException(
                'Invalid header line for Content-Encoding string: "' . $name . '"'
            );
        }
                $header = new static($value);
        return $header;
    }
    public function __construct($value = null)
    {
        if ($value) {
            HeaderValue::assertValid($value);
            $this->value = $value;
        }
    }
    public function getFieldName()
    {
        return 'Content-Encoding';
    }
    public function getFieldValue()
    {
        return $this->value;
    }
    public function toString()
    {
        return 'Content-Encoding: ' . $this->getFieldValue();
    }
}
