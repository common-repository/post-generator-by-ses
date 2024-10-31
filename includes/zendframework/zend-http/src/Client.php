<?php
namespace Zend\Http;
use ArrayIterator;
use Traversable;
use Zend\Http\Client\Adapter\Curl;
use Zend\Http\Client\Adapter\Socket;
use Zend\Stdlib;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\ErrorHandler;
use Zend\Uri\Http;
class Client implements Stdlib\DispatchableInterface
{
    const AUTH_BASIC  = 'basic';
    const AUTH_DIGEST = 'digest';
    const ENC_URLENCODED = 'application/x-www-form-urlencoded';
    const ENC_FORMDATA   = 'multipart/form-data';
    const DIGEST_REALM  = 'realm';
    const DIGEST_QOP    = 'qop';
    const DIGEST_NONCE  = 'nonce';
    const DIGEST_OPAQUE = 'opaque';
    const DIGEST_NC     = 'nc';
    const DIGEST_CNONCE = 'cnonce';
    protected $response;
    protected $request;
    protected $adapter;
    protected $auth = [];
    protected $streamName;
    protected $streamHandle = null;
    protected $cookies = [];
    protected $encType = '';
    protected $lastRawRequest;
    protected $lastRawResponse;
    protected $redirectCounter = 0;
    protected $config = [
        'maxredirects'    => 5,
        'strictredirects' => false,
        'useragent'       => Client::class,
        'timeout'         => 10,
        'connecttimeout'  => null,
        'adapter'         => Socket::class,
        'httpversion'     => Request::VERSION_11,
        'storeresponse'   => true,
        'keepalive'       => false,
        'outputstream'    => false,
        'encodecookies'   => true,
        'argseparator'    => null,
        'rfc3986strict'   => false,
        'sslcafile'       => null,
        'sslcapath'       => null,
    ];
    protected static $fileInfoDb;
    public function __construct($uri = null, $options = null)
    {
        if ($uri !== null) {
            $this->setUri($uri);
        }
        if ($options !== null) {
            $this->setOptions($options);
        }
    }
    public function setOptions($options = [])
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }
        if (! is_array($options)) {
            throw new Client\Exception\InvalidArgumentException('Config parameter is not valid');
        }
        foreach ($options as $k => $v) {
            $this->config[str_replace(['-', '_', ' ', '.'], '', strtolower($k))] = $v;         }
                if ($this->adapter instanceof Client\Adapter\AdapterInterface) {
            $this->adapter->setOptions($options);
        }
        return $this;
    }
    public function setAdapter($adapter)
    {
        if (is_string($adapter)) {
            if (! class_exists($adapter)) {
                throw new Client\Exception\InvalidArgumentException(
                    'Unable to locate adapter class "' . $adapter . '"'
                );
            }
            $adapter = new $adapter;
        }
        if (! $adapter instanceof Client\Adapter\AdapterInterface) {
            throw new Client\Exception\InvalidArgumentException('Passed adapter is not a HTTP connection adapter');
        }
        $this->adapter = $adapter;
        $config = $this->config;
        unset($config['adapter']);
        $this->adapter->setOptions($config);
        return $this;
    }
    public function getAdapter()
    {
        if (! $this->adapter) {
            $this->setAdapter($this->config['adapter']);
        }
        return $this->adapter;
    }
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }
    public function getRequest()
    {
        if (empty($this->request)) {
            $this->request = new Request();
            $this->request->setAllowCustomMethods(false);
        }
        return $this->request;
    }
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }
    public function getResponse()
    {
        if (empty($this->response)) {
            $this->response = new Response();
        }
        return $this->response;
    }
    public function getLastRawRequest()
    {
        return $this->lastRawRequest;
    }
    public function getLastRawResponse()
    {
        return $this->lastRawResponse;
    }
    public function getRedirectionsCount()
    {
        return $this->redirectCounter;
    }
    public function setUri($uri)
    {
        if (! empty($uri)) {
                        $lastHost = $this->getRequest()->getUri()->getHost();
            $this->getRequest()->setUri($uri);
                                                $nextHost = $this->getRequest()->getUri()->getHost();
            if (! preg_match('/' . preg_quote($lastHost, '/') . '$/i', $nextHost)) {
                $this->clearAuth();
            }
                        if ($this->getUri()->getUser() && $this->getUri()->getPassword()) {
                $this->setAuth($this->getUri()->getUser(), $this->getUri()->getPassword());
            }
                        if (! $this->getUri()->getPort()) {
                $this->getUri()->setPort(($this->getUri()->getScheme() == 'https' ? 443 : 80));
            }
        }
        return $this;
    }
    public function getUri()
    {
        return $this->getRequest()->getUri();
    }
    public function setMethod($method)
    {
        $method = $this->getRequest()->setMethod($method)->getMethod();
        if (empty($this->encType)
            && in_array(
                $method,
                [
                    Request::METHOD_POST,
                    Request::METHOD_PUT,
                    Request::METHOD_DELETE,
                    Request::METHOD_PATCH,
                    Request::METHOD_OPTIONS,
                ],
                true
            )
        ) {
            $this->setEncType(self::ENC_URLENCODED);
        }
        return $this;
    }
    public function getMethod()
    {
        return $this->getRequest()->getMethod();
    }
    public function setArgSeparator($argSeparator)
    {
        $this->setOptions(['argseparator' => $argSeparator]);
        return $this;
    }
    public function getArgSeparator()
    {
        $argSeparator = $this->config['argseparator'];
        if (empty($argSeparator)) {
            $argSeparator = ini_get('arg_separator.output');
            $this->setArgSeparator($argSeparator);
        }
        return $argSeparator;
    }
    public function setEncType($encType, $boundary = null)
    {
        if (null === $encType || empty($encType)) {
            $this->encType = null;
            return $this;
        }
        if (! empty($boundary)) {
            $encType .= sprintf('; boundary=%s', $boundary);
        }
        $this->encType = $encType;
        return $this;
    }
    public function getEncType()
    {
        return $this->encType;
    }
    public function setRawBody($body)
    {
        $this->getRequest()->setContent($body);
        return $this;
    }
    public function setParameterPost(array $post)
    {
        $this->getRequest()->getPost()->fromArray($post);
        return $this;
    }
    public function setParameterGet(array $query)
    {
        $this->getRequest()->getQuery()->fromArray($query);
        return $this;
    }
    public function resetParameters($clearCookies = false )
    {
        $clearAuth = true;
        if (func_num_args() > 1) {
            $clearAuth = func_get_arg(1);
        }
        $uri = $this->getUri();
        $this->streamName      = null;
        $this->encType         = null;
        $this->request         = null;
        $this->response        = null;
        $this->lastRawRequest  = null;
        $this->lastRawResponse = null;
        $this->setUri($uri);
        if ($clearCookies) {
            $this->clearCookies();
        }
        if ($clearAuth) {
            $this->clearAuth();
        }
        return $this;
    }
    public function getCookies()
    {
        return $this->cookies;
    }
    protected function getCookieId($cookie)
    {
        if (($cookie instanceof Header\SetCookie) || ($cookie instanceof Header\Cookie)) {
            return $cookie->getName() . $cookie->getDomain() . $cookie->getPath();
        }
        return false;
    }
    public function addCookie(
        $cookie,
        $value = null,
        $expire = null,
        $path = null,
        $domain = null,
        $secure = false,
        $httponly = true,
        $maxAge = null,
        $version = null
    ) {
        if (is_array($cookie) || $cookie instanceof ArrayIterator) {
            foreach ($cookie as $setCookie) {
                if ($setCookie instanceof Header\SetCookie) {
                    $this->cookies[$this->getCookieId($setCookie)] = $setCookie;
                } else {
                    throw new Exception\InvalidArgumentException('The cookie parameter is not a valid Set-Cookie type');
                }
            }
        } elseif (is_string($cookie) && $value !== null) {
            $setCookie = new Header\SetCookie(
                $cookie,
                $value,
                $expire,
                $path,
                $domain,
                $secure,
                $httponly,
                $maxAge,
                $version
            );
            $this->cookies[$this->getCookieId($setCookie)] = $setCookie;
        } elseif ($cookie instanceof Header\SetCookie) {
            $this->cookies[$this->getCookieId($cookie)] = $cookie;
        } else {
            throw new Exception\InvalidArgumentException('Invalid parameter type passed as Cookie');
        }
        return $this;
    }
    public function setCookies($cookies)
    {
        if (is_array($cookies)) {
            $this->clearCookies();
            foreach ($cookies as $name => $value) {
                $this->addCookie($name, $value);
            }
        } else {
            throw new Exception\InvalidArgumentException('Invalid cookies passed as parameter, it must be an array');
        }
        return $this;
    }
    public function clearCookies()
    {
        $this->cookies = [];
    }
    public function setHeaders($headers)
    {
        if (is_array($headers)) {
            $newHeaders = new Headers();
            $newHeaders->addHeaders($headers);
            $this->getRequest()->setHeaders($newHeaders);
        } elseif ($headers instanceof Headers) {
            $this->getRequest()->setHeaders($headers);
        } else {
            throw new Exception\InvalidArgumentException('Invalid parameter headers passed');
        }
        return $this;
    }
    public function hasHeader($name)
    {
        $headers = $this->getRequest()->getHeaders();
        if ($headers instanceof Headers) {
            return $headers->has($name);
        }
        return false;
    }
    public function getHeader($name)
    {
        $headers = $this->getRequest()->getHeaders();
        if ($headers instanceof Headers) {
            if ($headers->get($name)) {
                return $headers->get($name)->getFieldValue();
            }
        }
        return false;
    }
    public function setStream($streamfile = true)
    {
        $this->setOptions(['outputstream' => $streamfile]);
        return $this;
    }
    public function getStream()
    {
        if (null !== $this->streamName) {
            return $this->streamName;
        }
        return $this->config['outputstream'];
    }
    protected function openTempStream()
    {
        $this->streamName = $this->config['outputstream'];
        if (! is_string($this->streamName)) {
                        $this->streamName = tempnam(
                isset($this->config['streamtmpdir']) ? $this->config['streamtmpdir'] : sys_get_temp_dir(),
                Client::class
            );
        }
        ErrorHandler::start();
        $fp    = fopen($this->streamName, 'w+b');
        $error = ErrorHandler::stop();
        if (false === $fp) {
            if ($this->adapter instanceof Client\Adapter\AdapterInterface) {
                $this->adapter->close();
            }
            throw new Exception\RuntimeException(sprintf('Could not open temp file %s', $this->streamName), 0, $error);
        }
        return $fp;
    }
    public function setAuth($user, $password, $type = self::AUTH_BASIC)
    {
        if (! defined('static::AUTH_' . strtoupper($type))) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid or not supported authentication type: \'%s\'',
                $type
            ));
        }
        if (empty($user)) {
            throw new Exception\InvalidArgumentException('The username cannot be empty');
        }
        $this->auth = [
            'user'     => $user,
            'password' => $password,
            'type'     => $type,
        ];
        return $this;
    }
    public function clearAuth()
    {
        $this->auth = [];
    }
    protected function calcAuthDigest($user, $password, $type = self::AUTH_BASIC, $digest = [], $entityBody = null)
    {
        if (! defined('self::AUTH_' . strtoupper($type))) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid or not supported authentication type: \'%s\'',
                $type
            ));
        }
        $response = false;
        switch (strtolower($type)) {
            case self::AUTH_BASIC:
                                if (strpos($user, ':') !== false) {
                    throw new Exception\InvalidArgumentException(
                        'The user name cannot contain \':\' in Basic HTTP authentication'
                    );
                }
                $response = base64_encode($user . ':' . $password);
                break;
            case self::AUTH_DIGEST:
                if (empty($digest)) {
                    throw new Exception\InvalidArgumentException('The digest cannot be empty');
                }
                foreach ($digest as $key => $value) {
                    if (! defined('self::DIGEST_' . strtoupper($key))) {
                        throw new Exception\InvalidArgumentException(sprintf(
                            'Invalid or not supported digest authentication parameter: \'%s\'',
                            $key
                        ));
                    }
                }
                $ha1 = md5($user . ':' . $digest['realm'] . ':' . $password);
                if (empty($digest['qop']) || strtolower($digest['qop']) == 'auth') {
                    $ha2 = md5($this->getMethod() . ':' . $this->getUri()->getPath());
                } elseif (strtolower($digest['qop']) == 'auth-int') {
                    if (empty($entityBody)) {
                        throw new Exception\InvalidArgumentException(
                            'I cannot use the auth-int digest authentication without the entity body'
                        );
                    }
                    $ha2 = md5($this->getMethod() . ':' . $this->getUri()->getPath() . ':' . md5($entityBody));
                }
                if (empty($digest['qop'])) {
                    $response = md5($ha1 . ':' . $digest['nonce'] . ':' . $ha2);
                } else {
                    $response = md5($ha1 . ':' . $digest['nonce'] . ':' . $digest['nc']
                                    . ':' . $digest['cnonce'] . ':' . $digest['qoc'] . ':' . $ha2);
                }
                break;
        }
        return $response;
    }
    public function dispatch(Stdlib\RequestInterface $request, Stdlib\ResponseInterface $response = null)
    {
        $response = $this->send($request);
        return $response;
    }
    public function send(Request $request = null)
    {
        if ($request !== null) {
            $this->setRequest($request);
        }
        $this->redirectCounter = 0;
        $adapter = $this->getAdapter();
                do {
                        $uri = $this->getUri();
                        $query = $this->getRequest()->getQuery();
            if (! empty($query)) {
                $queryArray = $query->toArray();
                if (! empty($queryArray)) {
                    $newUri = $uri->toString();
                    $queryString = http_build_query($queryArray, null, $this->getArgSeparator());
                    if ($this->config['rfc3986strict']) {
                        $queryString = str_replace('+', '%20', $queryString);
                    }
                    if (strpos($newUri, '?') !== false) {
                        $newUri .= $this->getArgSeparator() . $queryString;
                    } else {
                        $newUri .= '?' . $queryString;
                    }
                    $uri = new Http($newUri);
                }
            }
                        if (! $uri->getPort()) {
                $uri->setPort($uri->getScheme() == 'https' ? 443 : 80);
            }
                        $method = $this->getRequest()->getMethod();
                        $this->setMethod($method);
                        $body = $this->prepareBody();
                        $headers = $this->prepareHeaders($body, $uri);
            $secure = $uri->getScheme() == 'https';
                        $cookie = $this->prepareCookies($uri->getHost(), $uri->getPath(), $secure);
            if ($cookie->getFieldValue()) {
                $headers['Cookie'] = $cookie->getFieldValue();
            }
                        if (is_resource($body) && ! ($adapter instanceof Client\Adapter\StreamInterface)) {
                throw new Client\Exception\RuntimeException('Adapter does not support streaming');
            }
            $this->streamHandle = null;
                                    $response = $this->doRequest($uri, $method, $secure, $headers, $body);
            $stream = $this->streamHandle;
            $this->streamHandle = null;
            if (! $response) {
                if ($stream !== null) {
                    fclose($stream);
                }
                throw new Exception\RuntimeException('Unable to read response, or response is empty');
            }
            if ($this->config['storeresponse']) {
                $this->lastRawResponse = $response;
            } else {
                $this->lastRawResponse = null;
            }
            if ($this->config['outputstream']) {
                if ($stream === null) {
                    $stream = $this->getStream();
                    if (! is_resource($stream) && is_string($stream)) {
                        $stream = fopen($stream, 'r');
                    }
                }
                $streamMetaData = stream_get_meta_data($stream);
                if ($streamMetaData['seekable']) {
                    rewind($stream);
                }
                                $adapter->setOutputStream(null);
                $response = Response\Stream::fromStream($response, $stream);
                $response->setStreamName($this->streamName);
                if (! is_string($this->config['outputstream'])) {
                                        $response->setCleanup(true);
                }
            } else {
                $response = $this->getResponse()->fromString($response);
            }
                        $setCookies = $response->getCookie();
            if (! empty($setCookies)) {
                $this->addCookie($setCookies);
            }
                        if ($response->isRedirect() && ($response->getHeaders()->has('Location'))) {
                                                $location = trim($response->getHeaders()->get('Location')->getFieldValue());
                                                if ($response->getStatusCode() == 303
                    || ((! $this->config['strictredirects'])
                        && ($response->getStatusCode() == 302 || $response->getStatusCode() == 301))
                ) {
                    $this->resetParameters(false, false);
                    $this->setMethod(Request::METHOD_GET);
                }
                                if (($scheme = substr($location, 0, 6))
                    && ($scheme == 'http:/' || $scheme == 'https:')
                ) {
                                        $this->setUri($location);
                } else {
                                        if (strpos($location, '?') !== false) {
                        list($location, $query) = explode('?', $location, 2);
                    } else {
                        $query = '';
                    }
                    $this->getUri()->setQuery($query);
                                        if (strpos($location, '/') === 0) {
                        $this->getUri()->setPath($location);
                                            } else {
                                                $path = $this->getUri()->getPath();
                        $path = rtrim(substr($path, 0, strrpos($path, '/')), '/');
                        $this->getUri()->setPath($path . '/' . $location);
                    }
                }
                ++$this->redirectCounter;
            } else {
                                break;
            }
        } while ($this->redirectCounter <= $this->config['maxredirects']);
        $this->response = $response;
        return $response;
    }
    public function reset()
    {
        $this->resetParameters();
        $this->clearAuth();
        $this->clearCookies();
        return $this;
    }
    public function setFileUpload($filename, $formname, $data = null, $ctype = null)
    {
        if ($data === null) {
            ErrorHandler::start();
            $data  = file_get_contents($filename);
            $error = ErrorHandler::stop();
            if ($data === false) {
                throw new Exception\RuntimeException(sprintf(
                    'Unable to read file \'%s\' for upload',
                    $filename
                ), 0, $error);
            }
            if (! $ctype) {
                $ctype = $this->detectFileMimeType($filename);
            }
        }
        $this->getRequest()->getFiles()->set($filename, [
            'formname' => $formname,
            'filename' => basename($filename),
            'ctype' => $ctype,
            'data' => $data,
        ]);
        return $this;
    }
    public function removeFileUpload($filename)
    {
        $file = $this->getRequest()->getFiles()->get($filename);
        if (! empty($file)) {
            $this->getRequest()->getFiles()->set($filename, null);
            return true;
        }
        return false;
    }
    protected function prepareCookies($domain, $path, $secure)
    {
        $validCookies = [];
        if (! empty($this->cookies)) {
            foreach ($this->cookies as $id => $cookie) {
                if ($cookie->isExpired()) {
                    unset($this->cookies[$id]);
                    continue;
                }
                if ($cookie->isValidForRequest($domain, $path, $secure)) {
                                        $validCookies[$cookie->getName()] = $cookie;
                }
            }
        }
        $cookies = Header\Cookie::fromSetCookieArray($validCookies);
        $cookies->setEncodeValue($this->config['encodecookies']);
        return $cookies;
    }
    protected function prepareHeaders($body, $uri)
    {
        $headers = [];
                if ($this->config['httpversion'] == Request::VERSION_11) {
            $host = $uri->getHost();
                        if (! (($uri->getScheme() == 'http' && $uri->getPort() == 80)
                || ($uri->getScheme() == 'https' && $uri->getPort() == 443))
            ) {
                $host .= ':' . $uri->getPort();
            }
            $headers['Host'] = $host;
        }
                if (! $this->getRequest()->getHeaders()->has('Connection')) {
            if (! $this->config['keepalive']) {
                $headers['Connection'] = 'close';
            }
        }
                        if (! $this->getRequest()->getHeaders()->has('Accept-Encoding')) {
            if (function_exists('gzinflate')) {
                $headers['Accept-Encoding'] = 'gzip, deflate';
            } else {
                $headers['Accept-Encoding'] = 'identity';
            }
        }
                if (! $this->getRequest()->getHeaders()->has('User-Agent') && isset($this->config['useragent'])) {
            $headers['User-Agent'] = $this->config['useragent'];
        }
                if (! empty($this->auth)) {
            switch ($this->auth['type']) {
                case self::AUTH_BASIC:
                    $auth = $this->calcAuthDigest($this->auth['user'], $this->auth['password'], $this->auth['type']);
                    if ($auth !== false) {
                        $headers['Authorization'] = 'Basic ' . $auth;
                    }
                    break;
                case self::AUTH_DIGEST:
                    if (! $this->adapter instanceof Client\Adapter\Curl) {
                        throw new Exception\RuntimeException(sprintf(
                            'The digest authentication is only available for curl adapters (%s)',
                            Curl::class
                        ));
                    }
                    $this->adapter->setCurlOption(CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
                    $this->adapter->setCurlOption(CURLOPT_USERPWD, $this->auth['user'] . ':' . $this->auth['password']);
            }
        }
                $encType = $this->getEncType();
        if (! empty($encType)) {
            $headers['Content-Type'] = $encType;
        }
        if (! empty($body)) {
            if (is_resource($body)) {
                $fstat = fstat($body);
                $headers['Content-Length'] = $fstat['size'];
            } else {
                $headers['Content-Length'] = strlen($body);
            }
        }
                        $requestHeaders = $this->getRequest()->getHeaders();
        foreach ($requestHeaders as $requestHeaderElement) {
            $headers[$requestHeaderElement->getFieldName()] = $requestHeaderElement->getFieldValue();
        }
        return $headers;
    }
    protected function prepareBody()
    {
                if ($this->getRequest()->isTrace()) {
            return '';
        }
        $rawBody = $this->getRequest()->getContent();
        if (! empty($rawBody)) {
            return $rawBody;
        }
        $body = '';
        $hasFiles = false;
        if (! $this->getRequest()->getHeaders()->has('Content-Type')) {
            $hasFiles = ! empty($this->getRequest()->getFiles()->toArray());
                        if ($hasFiles) {
                $this->setEncType(self::ENC_FORMDATA);
            }
        } else {
            $this->setEncType($this->getHeader('Content-Type'));
        }
                if (! empty($this->getRequest()->getPost()->toArray()) || $hasFiles) {
            if (stripos($this->getEncType(), self::ENC_FORMDATA) === 0) {
                $boundary = '---ZENDHTTPCLIENT-' . md5(microtime());
                $this->setEncType(self::ENC_FORMDATA, $boundary);
                                $params = self::flattenParametersArray($this->getRequest()->getPost()->toArray());
                foreach ($params as $pp) {
                    $body .= $this->encodeFormData($boundary, $pp[0], $pp[1]);
                }
                                foreach ($this->getRequest()->getFiles()->toArray() as $file) {
                    $fhead = ['Content-Type' => $file['ctype']];
                    $body .= $this->encodeFormData(
                        $boundary,
                        $file['formname'],
                        $file['data'],
                        $file['filename'],
                        $fhead
                    );
                }
                $body .= '--' . $boundary . '--' . "\r\n";
            } elseif (stripos($this->getEncType(), self::ENC_URLENCODED) === 0) {
                                $body = http_build_query($this->getRequest()->getPost()->toArray(), null, '&');
            } else {
                throw new Client\Exception\RuntimeException(sprintf(
                    'Cannot handle content type \'%s\' automatically',
                    $this->encType
                ));
            }
        }
        return $body;
    }
    protected function detectFileMimeType($file)
    {
        $type = null;
                if (function_exists('finfo_open')) {
            if (static::$fileInfoDb === null) {
                ErrorHandler::start();
                static::$fileInfoDb = finfo_open(FILEINFO_MIME);
                ErrorHandler::stop();
            }
            if (static::$fileInfoDb) {
                $type = finfo_file(static::$fileInfoDb, $file);
            }
        } elseif (function_exists('mime_content_type')) {
            $type = mime_content_type($file);
        }
                if (! $type) {
            $type = 'application/octet-stream';
        }
        return $type;
    }
    public function encodeFormData($boundary, $name, $value, $filename = null, $headers = [])
    {
        $ret = '--' . $boundary . "\r\n"
            . 'Content-Disposition: form-data; name="' . $name . '"';
        if ($filename) {
            $ret .= '; filename="' . $filename . '"';
        }
        $ret .= "\r\n";
        foreach ($headers as $hname => $hvalue) {
            $ret .= $hname . ': ' . $hvalue . "\r\n";
        }
        $ret .= "\r\n";
        $ret .= $value . "\r\n";
        return $ret;
    }
    protected function flattenParametersArray($parray, $prefix = null)
    {
        if (! is_array($parray)) {
            return $parray;
        }
        $parameters = [];
        foreach ($parray as $name => $value) {
                        if ($prefix) {
                if (is_int($name)) {
                    $key = $prefix . '[]';
                } else {
                    $key = $prefix . sprintf('[%s]', $name);
                }
            } else {
                $key = $name;
            }
            if (is_array($value)) {
                $parameters = array_merge($parameters, $this->flattenParametersArray($value, $key));
            } else {
                $parameters[] = [$key, $value];
            }
        }
        return $parameters;
    }
    protected function doRequest(Http $uri, $method, $secure = false, $headers = [], $body = '')
    {
                $this->adapter->connect($uri->getHost(), $uri->getPort(), $secure);
        if ($this->config['outputstream']) {
            if ($this->adapter instanceof Client\Adapter\StreamInterface) {
                $this->streamHandle = $this->openTempStream();
                $this->adapter->setOutputStream($this->streamHandle);
            } else {
                throw new Exception\RuntimeException('Adapter does not support streaming');
            }
        }
                $this->lastRawRequest = $this->adapter->write(
            $method,
            $uri,
            $this->config['httpversion'],
            $headers,
            $body
        );
        return $this->adapter->read();
    }
    public static function encodeAuthHeader($user, $password, $type = self::AUTH_BASIC)
    {
        switch ($type) {
            case self::AUTH_BASIC:
                                if (strpos($user, ':') !== false) {
                    throw new Client\Exception\InvalidArgumentException(
                        'The user name cannot contain \':\' in \'Basic\' HTTP authentication'
                    );
                }
                return 'Basic ' . base64_encode($user . ':' . $password);
            default:
                throw new Client\Exception\InvalidArgumentException(sprintf(
                    'Not a supported HTTP authentication type: \'%s\'',
                    $type
                ));
        }
        return;
    }
}
