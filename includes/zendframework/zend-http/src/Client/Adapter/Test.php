<?php
namespace Zend\Http\Client\Adapter;
use Traversable;
use Zend\Http\Response;
use Zend\Stdlib\ArrayUtils;
class Test implements AdapterInterface
{
    protected $config = [];
    protected $responses = ["HTTP/1.1 400 Bad Request\r\n\r\n"];
    protected $responseIndex = 0;
    protected $nextRequestWillFail = false;
    public function __construct()
    {
    }
    public function setNextRequestWillFail($flag)
    {
        $this->nextRequestWillFail = (bool) $flag;
        return $this;
    }
    public function setOptions($options = [])
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }
        if (! is_array($options)) {
            throw new Exception\InvalidArgumentException(
                'Array or Traversable object expected, got ' . gettype($options)
            );
        }
        foreach ($options as $k => $v) {
            $this->config[strtolower($k)] = $v;
        }
    }
    public function connect($host, $port = 80, $secure = false)
    {
        if ($this->nextRequestWillFail) {
            $this->nextRequestWillFail = false;
            throw new Exception\RuntimeException('Request failed');
        }
    }
    public function write($method, $uri, $httpVer = '1.1', $headers = [], $body = '')
    {
                $path = $uri->getPath();
        if (empty($path)) {
            $path = '/';
        }
        $query = $uri->getQuery();
        $path .= $query ? '?' . $query : '';
        $request = $method . ' ' . $path . ' HTTP/' . $httpVer . "\r\n";
        foreach ($headers as $k => $v) {
            if (is_string($k)) {
                $v = ucfirst($k) . ': ' . $v;
            }
            $request .= $v . "\r\n";
        }
                $request .= "\r\n" . $body;
        return $request;
    }
    public function read()
    {
        if ($this->responseIndex >= count($this->responses)) {
            $this->responseIndex = 0;
        }
        return $this->responses[$this->responseIndex++];
    }
    public function close()
    {
    }
    public function setResponse($response)
    {
        if ($response instanceof Response) {
            $response = $response->toString();
        }
        $this->responses = (array) $response;
        $this->responseIndex = 0;
    }
    public function addResponse($response)
    {
        if ($response instanceof Response) {
            $response = $response->toString();
        }
        $this->responses[] = $response;
    }
    public function setResponseIndex($index)
    {
        if ($index < 0 || $index >= count($this->responses)) {
            throw new Exception\OutOfRangeException(
                'Index out of range of response buffer size'
            );
        }
        $this->responseIndex = $index;
    }
}
