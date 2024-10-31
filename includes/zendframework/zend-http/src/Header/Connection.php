<?php
namespace Zend\Http\Header;
class Connection implements HeaderInterface
{
    const CONNECTION_CLOSE      = 'close';
    const CONNECTION_KEEP_ALIVE = 'keep-alive';
    protected $value = self::CONNECTION_KEEP_ALIVE;
    public static function fromString($headerLine)
    {
        $header = new static();
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
                if (strtolower($name) !== 'connection') {
            throw new Exception\InvalidArgumentException('Invalid header line for Connection string: "' . $name . '"');
        }
        $header->setValue(trim($value));
        return $header;
    }
    public function setPersistent($flag)
    {
        $this->value = (bool) $flag
            ? self::CONNECTION_KEEP_ALIVE
            : self::CONNECTION_CLOSE;
        return $this;
    }
    public function isPersistent()
    {
        return ($this->value === self::CONNECTION_KEEP_ALIVE);
    }
    public function setValue($value)
    {
        HeaderValue::assertValid($value);
        $this->value = strtolower($value);
        return $this;
    }
    public function getFieldName()
    {
        return 'Connection';
    }
    public function getFieldValue()
    {
        return $this->value;
    }
    public function toString()
    {
        return 'Connection: ' . $this->getFieldValue();
    }
}
