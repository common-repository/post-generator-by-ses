<?php
namespace Zend\Http\Client\Adapter;
use Traversable;
use Zend\Http\Client\Adapter\AdapterInterface as HttpAdapter;
use Zend\Http\Client\Adapter\Exception as AdapterException;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\ErrorHandler;
class Socket implements HttpAdapter, StreamInterface
{
    protected static $sslCryptoTypes = [
        'ssl'   => STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
        'sslv2' => STREAM_CRYPTO_METHOD_SSLv2_CLIENT,
        'sslv3' => STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
        'tls'   => STREAM_CRYPTO_METHOD_TLS_CLIENT,
    ];
    protected $socket;
    protected $connectedTo = [null, null];
    protected $outStream;
    protected $config = [
        'persistent'            => false,
        'ssltransport'          => 'ssl',
        'sslcert'               => null,
        'sslpassphrase'         => null,
        'sslverifypeer'         => true,
        'sslcafile'             => null,
        'sslcapath'             => null,
        'sslallowselfsigned'    => false,
        'sslusecontext'         => false,
        'sslverifypeername'     => true,
    ];
    protected $method;
    protected $context;
    public function __construct()
    {
    }
    public function setOptions($options = [])
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }
        if (! is_array($options)) {
            throw new AdapterException\InvalidArgumentException(
                'Array or Zend\Config object expected, got ' . gettype($options)
            );
        }
        foreach ($options as $k => $v) {
            $this->config[strtolower($k)] = $v;
        }
    }
    public function getConfig()
    {
        return $this->config;
    }
    public function setStreamContext($context)
    {
        if (is_resource($context) && get_resource_type($context) == 'stream-context') {
            $this->context = $context;
        } elseif (is_array($context)) {
            $this->context = stream_context_create($context);
        } else {
                        throw new AdapterException\InvalidArgumentException(sprintf(
                'Expecting either a stream context resource or array, got %s',
                gettype($context)
            ));
        }
        return $this;
    }
    public function getStreamContext()
    {
        if (! $this->context) {
            $this->context = stream_context_create();
        }
        return $this->context;
    }
    public function connect($host, $port = 80, $secure = false)
    {
                $connectedHost = (strpos($this->connectedTo[0], '://'))
            ? substr($this->connectedTo[0], (strpos($this->connectedTo[0], '://') + 3), strlen($this->connectedTo[0]))
            : $this->connectedTo[0];
        if ($connectedHost != $host || $this->connectedTo[1] != $port) {
            if (is_resource($this->socket)) {
                $this->close();
            }
        }
                if (! is_resource($this->socket) || ! $this->config['keepalive']) {
            $context = $this->getStreamContext();
            if ($secure || $this->config['sslusecontext']) {
                if ($this->config['sslverifypeer'] !== null) {
                    if (! stream_context_set_option($context, 'ssl', 'verify_peer', $this->config['sslverifypeer'])) {
                        throw new AdapterException\RuntimeException('Unable to set sslverifypeer option');
                    }
                }
                if ($this->config['sslcafile']) {
                    if (! stream_context_set_option($context, 'ssl', 'cafile', $this->config['sslcafile'])) {
                        throw new AdapterException\RuntimeException('Unable to set sslcafile option');
                    }
                }
                if ($this->config['sslcapath']) {
                    if (! stream_context_set_option($context, 'ssl', 'capath', $this->config['sslcapath'])) {
                        throw new AdapterException\RuntimeException('Unable to set sslcapath option');
                    }
                }
                if ($this->config['sslallowselfsigned'] !== null) {
                    if (! stream_context_set_option(
                        $context,
                        'ssl',
                        'allow_self_signed',
                        $this->config['sslallowselfsigned']
                    )) {
                        throw new AdapterException\RuntimeException('Unable to set sslallowselfsigned option');
                    }
                }
                if ($this->config['sslcert'] !== null) {
                    if (! stream_context_set_option($context, 'ssl', 'local_cert', $this->config['sslcert'])) {
                        throw new AdapterException\RuntimeException('Unable to set sslcert option');
                    }
                }
                if ($this->config['sslpassphrase'] !== null) {
                    if (! stream_context_set_option($context, 'ssl', 'passphrase', $this->config['sslpassphrase'])) {
                        throw new AdapterException\RuntimeException('Unable to set sslpassphrase option');
                    }
                }
                if ($this->config['sslverifypeername'] !== null) {
                    if (! stream_context_set_option(
                        $context,
                        'ssl',
                        'verify_peer_name',
                        $this->config['sslverifypeername']
                    )) {
                        throw new AdapterException\RuntimeException('Unable to set sslverifypeername option');
                    }
                }
            }
            $flags = STREAM_CLIENT_CONNECT;
            if ($this->config['persistent']) {
                $flags |= STREAM_CLIENT_PERSISTENT;
            }
            if (isset($this->config['connecttimeout'])) {
                $connectTimeout = $this->config['connecttimeout'];
            } else {
                $connectTimeout = $this->config['timeout'];
            }
            ErrorHandler::start();
            $this->socket = stream_socket_client(
                $host . ':' . $port,
                $errno,
                $errstr,
                (int) $connectTimeout,
                $flags,
                $context
            );
            $error = ErrorHandler::stop();
            if (! $this->socket) {
                $this->close();
                throw new AdapterException\RuntimeException(
                    sprintf(
                        'Unable to connect to %s:%d%s',
                        $host,
                        $port,
                        ($error ? ' . Error #' . $error->getCode() . ': ' . $error->getMessage() : '')
                    ),
                    0,
                    $error
                );
            }
                        if (! stream_set_timeout($this->socket, (int) $this->config['timeout'])) {
                throw new AdapterException\RuntimeException('Unable to set the connection timeout');
            }
            if ($secure || $this->config['sslusecontext']) {
                if ($this->config['ssltransport'] && isset(static::$sslCryptoTypes[$this->config['ssltransport']])) {
                    $sslCryptoMethod = static::$sslCryptoTypes[$this->config['ssltransport']];
                } else {
                    $sslCryptoMethod = STREAM_CRYPTO_METHOD_SSLv3_CLIENT;
                }
                ErrorHandler::start();
                $test  = stream_socket_enable_crypto($this->socket, true, $sslCryptoMethod);
                $error = ErrorHandler::stop();
                if (! $test || $error) {
                                        $errorString = '';
                    if (extension_loaded('openssl')) {
                        while (($sslError = openssl_error_string()) != false) {
                            $errorString .= sprintf('; SSL error: %s', $sslError);
                        }
                    }
                    $this->close();
                    if ((! $errorString) && $this->config['sslverifypeer']) {
                                                if (! ($this->config['sslcafile'] || $this->config['sslcapath'])) {
                            $errorString = 'make sure the "sslcafile" or "sslcapath" option are properly set for the '
                                . 'environment.';
                        } elseif ($this->config['sslcafile'] && ! is_file($this->config['sslcafile'])) {
                            $errorString = 'make sure the "sslcafile" option points to a valid SSL certificate file';
                        } elseif ($this->config['sslcapath'] && ! is_dir($this->config['sslcapath'])) {
                            $errorString = 'make sure the "sslcapath" option points to a valid SSL certificate '
                                . 'directory';
                        }
                    }
                    if ($errorString) {
                        $errorString = sprintf(': %s', $errorString);
                    }
                    throw new AdapterException\RuntimeException(sprintf(
                        'Unable to enable crypto on TCP connection %s%s',
                        $host,
                        $errorString
                    ), 0, $error);
                }
                $host = $this->config['ssltransport'] . '://' . $host;
            } else {
                $host = 'tcp://' . $host;
            }
                        $this->connectedTo = [$host, $port];
        }
    }
    public function write($method, $uri, $httpVer = '1.1', $headers = [], $body = '')
    {
                if (! $this->socket) {
            throw new AdapterException\RuntimeException('Trying to write but we are not connected');
        }
        $host = $uri->getHost();
        $host = (strtolower($uri->getScheme()) == 'https' ? $this->config['ssltransport'] : 'tcp') . '://' . $host;
        if ($this->connectedTo[0] != $host || $this->connectedTo[1] != $uri->getPort()) {
            throw new AdapterException\RuntimeException('Trying to write but we are connected to the wrong host');
        }
                $this->method = $method;
                $path = $uri->getPath();
        $query = $uri->getQuery();
        $path .= $query ? '?' . $query : '';
        $request = $method . ' ' . $path . ' HTTP/' . $httpVer . "\r\n";
        foreach ($headers as $k => $v) {
            if (is_string($k)) {
                $v = ucfirst($k) . ': ' . $v;
            }
            $request .= $v . "\r\n";
        }
        if (is_resource($body)) {
            $request .= "\r\n";
        } else {
                        $request .= "\r\n" . $body;
        }
                ErrorHandler::start();
        $test  = fwrite($this->socket, $request);
        $error = ErrorHandler::stop();
        if (false === $test) {
            throw new AdapterException\RuntimeException('Error writing request to server', 0, $error);
        }
        if (is_resource($body)) {
            if (stream_copy_to_stream($body, $this->socket) == 0) {
                throw new AdapterException\RuntimeException('Error writing request to server');
            }
        }
        return $request;
    }
    public function read()
    {
                $response = '';
        $gotStatus = false;
        while (($line = fgets($this->socket)) !== false) {
            $gotStatus = $gotStatus || (strpos($line, 'HTTP') !== false);
            if ($gotStatus) {
                $response .= $line;
                if (rtrim($line) === '') {
                    break;
                }
            }
        }
        $this->_checkSocketReadTimeout();
        $responseObj = Response::fromString($response);
        $statusCode = $responseObj->getStatusCode();
                if ($statusCode == 100 || $statusCode == 101) {
            return $this->read();
        }
                $headers = $responseObj->getHeaders();
        if ($statusCode == 304
            || $statusCode == 204
            || $this->method == Request::METHOD_HEAD
        ) {
                        $connection = $headers->get('connection');
            if ($connection && $connection->getFieldValue() == 'close') {
                $this->close();
            }
            return $response;
        }
                $transferEncoding = $headers->get('transfer-encoding');
        $contentLength = $headers->get('content-length');
        if ($transferEncoding !== false) {
            if (strtolower($transferEncoding->getFieldValue()) == 'chunked') {
                do {
                    $line  = fgets($this->socket);
                    $this->_checkSocketReadTimeout();
                    $chunk = $line;
                                        $chunksize = trim($line);
                    if (! ctype_xdigit($chunksize)) {
                        $this->close();
                        throw new AdapterException\RuntimeException(sprintf(
                            'Invalid chunk size "%s" unable to read chunked body',
                            $chunksize
                        ));
                    }
                                        $chunksize = hexdec($chunksize);
                                        $readTo = ftell($this->socket) + $chunksize;
                    do {
                        $currentPos = ftell($this->socket);
                        if ($currentPos >= $readTo) {
                            break;
                        }
                        if ($this->outStream) {
                            if (stream_copy_to_stream($this->socket, $this->outStream, $readTo - $currentPos) == 0) {
                                $this->_checkSocketReadTimeout();
                                break;
                            }
                        } else {
                            $line = fread($this->socket, $readTo - $currentPos);
                            if ($line === false || strlen($line) === 0) {
                                $this->_checkSocketReadTimeout();
                                break;
                            }
                            $chunk .= $line;
                        }
                    } while (! feof($this->socket));
                    ErrorHandler::start();
                    $chunk .= fgets($this->socket);
                    ErrorHandler::stop();
                    $this->_checkSocketReadTimeout();
                    if (! $this->outStream) {
                        $response .= $chunk;
                    }
                } while ($chunksize > 0);
            } else {
                $this->close();
                throw new AdapterException\RuntimeException(sprintf(
                    'Cannot handle "%s" transfer encoding',
                    $transferEncoding->getFieldValue()
                ));
            }
                                    if ($this->outStream) {
                $response = str_ireplace("Transfer-Encoding: chunked\r\n", '', $response);
            }
                } elseif ($contentLength !== false) {
                                    if (is_array($contentLength)) {
                $contentLength = $contentLength[count($contentLength) - 1];
            }
            $contentLength = $contentLength->getFieldValue();
            $currentPos = ftell($this->socket);
            for ($readTo = $currentPos + $contentLength;
                 $readTo > $currentPos;
                 $currentPos = ftell($this->socket)) {
                if ($this->outStream) {
                    if (stream_copy_to_stream($this->socket, $this->outStream, $readTo - $currentPos) == 0) {
                        $this->_checkSocketReadTimeout();
                        break;
                    }
                } else {
                    $chunk = fread($this->socket, $readTo - $currentPos);
                    if ($chunk === false || strlen($chunk) === 0) {
                        $this->_checkSocketReadTimeout();
                        break;
                    }
                    $response .= $chunk;
                }
                                if (feof($this->socket)) {
                    break;
                }
            }
                } else {
            do {
                if ($this->outStream) {
                    if (stream_copy_to_stream($this->socket, $this->outStream) == 0) {
                        $this->_checkSocketReadTimeout();
                        break;
                    }
                } else {
                    $buff = fread($this->socket, 8192);
                    if ($buff === false || strlen($buff) === 0) {
                        $this->_checkSocketReadTimeout();
                        break;
                    } else {
                        $response .= $buff;
                    }
                }
            } while (feof($this->socket) === false);
            $this->close();
        }
                $connection = $headers->get('connection');
        if ($connection && $connection->getFieldValue() == 'close') {
            $this->close();
        }
        return $response;
    }
    public function close()
    {
        if (is_resource($this->socket)) {
            ErrorHandler::start();
            fclose($this->socket);
            ErrorHandler::stop();
        }
        $this->socket = null;
        $this->connectedTo = [null, null];
    }
        protected function _checkSocketReadTimeout()
    {
                if ($this->socket) {
            $info = stream_get_meta_data($this->socket);
            $timedout = $info['timed_out'];
            if ($timedout) {
                $this->close();
                throw new AdapterException\TimeoutException(
                    sprintf('Read timed out after %d seconds', $this->config['timeout']),
                    AdapterException\TimeoutException::READ_TIMEOUT
                );
            }
        }
    }
    public function setOutputStream($stream)
    {
        $this->outStream = $stream;
        return $this;
    }
    public function __destruct()
    {
        if (! $this->config['persistent']) {
            if ($this->socket) {
                $this->close();
            }
        }
    }
}
