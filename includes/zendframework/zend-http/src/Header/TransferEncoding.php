<?php
namespace Zend\Http\Header;
class TransferEncoding implements HeaderInterface
{
    protected $value;
    public static function fromString($headerLine)
    {
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
                if (strtolower($name) !== 'transfer-encoding') {
            throw new Exception\InvalidArgumentException(
                'Invalid header line for Transfer-Encoding string: "' . $name . '"'
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
        return 'Transfer-Encoding';
    }
    public function getFieldValue()
    {
        return $this->value;
    }
    public function toString()
    {
        return 'Transfer-Encoding: ' . $this->getFieldValue();
    }
}
