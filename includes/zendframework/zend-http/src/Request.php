<?php
namespace Zend\Http;
use Zend\Stdlib\Parameters;
use Zend\Stdlib\ParametersInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Uri\Exception as UriException;
use Zend\Uri\Http as HttpUri;
class Request extends AbstractMessage implements RequestInterface
{
    const METHOD_OPTIONS  = 'OPTIONS';
    const METHOD_GET      = 'GET';
    const METHOD_HEAD     = 'HEAD';
    const METHOD_POST     = 'POST';
    const METHOD_PUT      = 'PUT';
    const METHOD_DELETE   = 'DELETE';
    const METHOD_TRACE    = 'TRACE';
    const METHOD_CONNECT  = 'CONNECT';
    const METHOD_PATCH    = 'PATCH';
    const METHOD_PROPFIND = 'PROPFIND';
    protected $method = self::METHOD_GET;
    protected $allowCustomMethods = true;
    protected $uri;
    protected $queryParams;
    protected $postParams;
    protected $fileParams;
    public static function fromString($string, $allowCustomMethods = true)
    {
        $request = new static();
        $request->setAllowCustomMethods($allowCustomMethods);
        $lines = explode("\r\n", $string);
                $matches   = null;
        $methods   = $allowCustomMethods
            ? '[\w-]+'
            : implode(
                '|',
                [
                    self::METHOD_OPTIONS,
                    self::METHOD_GET,
                    self::METHOD_HEAD,
                    self::METHOD_POST,
                    self::METHOD_PUT,
                    self::METHOD_DELETE,
                    self::METHOD_TRACE,
                    self::METHOD_CONNECT,
                    self::METHOD_PATCH,
                ]
            );
        $regex     = '#^(?P<method>' . $methods . ')\s(?P<uri>[^ ]*)(?:\sHTTP\/(?P<version>\d+\.\d+)){0,1}#';
        $firstLine = array_shift($lines);
        if (! preg_match($regex, $firstLine, $matches)) {
            throw new Exception\InvalidArgumentException(
                'A valid request line was not found in the provided string'
            );
        }
        $request->setMethod($matches['method']);
        $request->setUri($matches['uri']);
        $parsedUri = parse_url($matches['uri']);
        if (array_key_exists('query', $parsedUri)) {
            $parsedQuery = [];
            parse_str($parsedUri['query'], $parsedQuery);
            $request->setQuery(new Parameters($parsedQuery));
        }
        if (isset($matches['version'])) {
            $request->setVersion($matches['version']);
        }
        if (empty($lines)) {
            return $request;
        }
        $isHeader = true;
        $headers = $rawBody = [];
        while ($lines) {
            $nextLine = array_shift($lines);
            if ($nextLine == '') {
                $isHeader = false;
                continue;
            }
            if ($isHeader) {
                if (preg_match("/[\r\n]/", $nextLine)) {
                    throw new Exception\RuntimeException('CRLF injection detected');
                }
                $headers[] = $nextLine;
                continue;
            }
            if (empty($rawBody)
                && preg_match('/^[a-z0-9!#$%&\'*+.^_`|~-]+:$/i', $nextLine)
            ) {
                throw new Exception\RuntimeException('CRLF injection detected');
            }
            $rawBody[] = $nextLine;
        }
        if ($headers) {
            $request->headers = implode("\r\n", $headers);
        }
        if ($rawBody) {
            $request->setContent(implode("\r\n", $rawBody));
        }
        return $request;
    }
    public function setMethod($method)
    {
        $method = strtoupper($method);
        if (! defined('static::METHOD_' . $method) && ! $this->getAllowCustomMethods()) {
            throw new Exception\InvalidArgumentException('Invalid HTTP method passed');
        }
        $this->method = $method;
        return $this;
    }
    public function getMethod()
    {
        return $this->method;
    }
    public function setUri($uri)
    {
        if (is_string($uri)) {
            try {
                $uri = new HttpUri($uri);
            } catch (UriException\InvalidUriPartException $e) {
                throw new Exception\InvalidArgumentException(
                    sprintf('Invalid URI passed as string (%s)', (string) $uri),
                    $e->getCode(),
                    $e
                );
            }
        } elseif (! ($uri instanceof HttpUri)) {
            throw new Exception\InvalidArgumentException(
                'URI must be an instance of Zend\Uri\Http or a string'
            );
        }
        $this->uri = $uri;
        return $this;
    }
    public function getUri()
    {
        if ($this->uri === null || is_string($this->uri)) {
            $this->uri = new HttpUri($this->uri);
        }
        return $this->uri;
    }
    public function getUriString()
    {
        if ($this->uri instanceof HttpUri) {
            return $this->uri->toString();
        }
        return $this->uri;
    }
    public function setQuery(ParametersInterface $query)
    {
        $this->queryParams = $query;
        return $this;
    }
    public function getQuery($name = null, $default = null)
    {
        if ($this->queryParams === null) {
            $this->queryParams = new Parameters();
        }
        if ($name === null) {
            return $this->queryParams;
        }
        return $this->queryParams->get($name, $default);
    }
    public function setPost(ParametersInterface $post)
    {
        $this->postParams = $post;
        return $this;
    }
    public function getPost($name = null, $default = null)
    {
        if ($this->postParams === null) {
            $this->postParams = new Parameters();
        }
        if ($name === null) {
            return $this->postParams;
        }
        return $this->postParams->get($name, $default);
    }
    public function getCookie()
    {
        return $this->getHeaders()->get('Cookie');
    }
    public function setFiles(ParametersInterface $files)
    {
        $this->fileParams = $files;
        return $this;
    }
    public function getFiles($name = null, $default = null)
    {
        if ($this->fileParams === null) {
            $this->fileParams = new Parameters();
        }
        if ($name === null) {
            return $this->fileParams;
        }
        return $this->fileParams->get($name, $default);
    }
    public function getHeaders($name = null, $default = false)
    {
        if ($this->headers === null || is_string($this->headers)) {
                        $this->headers = (is_string($this->headers)) ? Headers::fromString($this->headers) : new Headers();
        }
        if ($name === null) {
            return $this->headers;
        }
        if ($this->headers->has($name)) {
            return $this->headers->get($name);
        }
        return $default;
    }
    public function getHeader($name, $default = false)
    {
        return $this->getHeaders($name, $default);
    }
    public function isOptions()
    {
        return ($this->method === self::METHOD_OPTIONS);
    }
    public function isPropFind()
    {
        return ($this->method === self::METHOD_PROPFIND);
    }
    public function isGet()
    {
        return ($this->method === self::METHOD_GET);
    }
    public function isHead()
    {
        return ($this->method === self::METHOD_HEAD);
    }
    public function isPost()
    {
        return ($this->method === self::METHOD_POST);
    }
    public function isPut()
    {
        return ($this->method === self::METHOD_PUT);
    }
    public function isDelete()
    {
        return ($this->method === self::METHOD_DELETE);
    }
    public function isTrace()
    {
        return ($this->method === self::METHOD_TRACE);
    }
    public function isConnect()
    {
        return ($this->method === self::METHOD_CONNECT);
    }
    public function isPatch()
    {
        return ($this->method === self::METHOD_PATCH);
    }
    public function isXmlHttpRequest()
    {
        $header = $this->getHeaders()->get('X_REQUESTED_WITH');
        return false !== $header && $header->getFieldValue() == 'XMLHttpRequest';
    }
    public function isFlashRequest()
    {
        $header = $this->getHeaders()->get('USER_AGENT');
        return false !== $header && stristr($header->getFieldValue(), ' flash');
    }
    public function renderRequestLine()
    {
        return $this->method . ' ' . (string) $this->uri . ' HTTP/' . $this->version;
    }
    public function toString()
    {
        $str = $this->renderRequestLine() . "\r\n";
        $str .= $this->getHeaders()->toString();
        $str .= "\r\n";
        $str .= $this->getContent();
        return $str;
    }
    public function getAllowCustomMethods()
    {
        return $this->allowCustomMethods;
    }
    public function setAllowCustomMethods($strictMethods)
    {
        $this->allowCustomMethods = (bool) $strictMethods;
    }
}
