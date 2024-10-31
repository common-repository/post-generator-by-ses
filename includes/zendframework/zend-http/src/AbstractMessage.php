<?php
namespace Zend\Http;
use Zend\Stdlib\Message;
abstract class AbstractMessage extends Message
{
    const VERSION_10 = '1.0';
    const VERSION_11 = '1.1';
    protected $version = self::VERSION_11;
    protected $headers;
    public function setVersion($version)
    {
        if ($version != self::VERSION_10 && $version != self::VERSION_11) {
            throw new Exception\InvalidArgumentException(
                'Not valid or not supported HTTP version: ' . $version
            );
        }
        $this->version = $version;
        return $this;
    }
    public function getVersion()
    {
        return $this->version;
    }
    public function setHeaders(Headers $headers)
    {
        $this->headers = $headers;
        return $this;
    }
    public function getHeaders()
    {
        if ($this->headers === null || is_string($this->headers)) {
                        $this->headers = (is_string($this->headers)) ? Headers::fromString($this->headers) : new Headers();
        }
        return $this->headers;
    }
    public function __toString()
    {
        return $this->toString();
    }
}
