<?php
namespace Zend\Http\Client\Adapter;
use Traversable;
use Zend\Http\Client\Adapter\AdapterInterface as HttpAdapter;
use Zend\Http\Client\Adapter\Exception as AdapterException;
use Zend\Stdlib\ArrayUtils;
class Curl implements HttpAdapter, StreamInterface
{
    const ERROR_OPERATION_TIMEDOUT = 28;
    protected $config = [];
    protected $connectedTo = [null, null];
    protected $curl;
    protected $invalidOverwritableCurlOptions;
    protected $response;
    protected $outputStream;
    public function __construct()
    {
        if (! extension_loaded('curl')) {
            throw new AdapterException\InitializationException(
                'cURL extension has to be loaded to use this Zend\Http\Client adapter'
            );
        }
        $this->invalidOverwritableCurlOptions = [
            CURLOPT_HTTPGET,
            CURLOPT_POST,
            CURLOPT_UPLOAD,
            CURLOPT_CUSTOMREQUEST,
            CURLOPT_HEADER,
            CURLOPT_RETURNTRANSFER,
            CURLOPT_HTTPHEADER,
            CURLOPT_INFILE,
            CURLOPT_INFILESIZE,
            CURLOPT_PORT,
            CURLOPT_MAXREDIRS,
            CURLOPT_CONNECTTIMEOUT,
        ];
    }
    public function setOptions($options = [])
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }
        if (! is_array($options)) {
            throw new AdapterException\InvalidArgumentException(sprintf(
                'Array or Traversable object expected, got %s',
                gettype($options)
            ));
        }
        foreach ($options as $k => $v) {
            unset($options[$k]);             $options[str_replace(['-', '_', ' ', '.'], '', strtolower($k))] = $v;         }
        if (isset($options['proxyuser']) && isset($options['proxypass'])) {
            $this->setCurlOption(CURLOPT_PROXYUSERPWD, $options['proxyuser'] . ':' . $options['proxypass']);
            unset($options['proxyuser'], $options['proxypass']);
        }
        if (isset($options['sslverifypeer'])) {
            $this->setCurlOption(CURLOPT_SSL_VERIFYPEER, $options['sslverifypeer']);
            unset($options['sslverifypeer']);
        }
        foreach ($options as $k => $v) {
            $option = strtolower($k);
            switch ($option) {
                case 'proxyhost':
                    $this->setCurlOption(CURLOPT_PROXY, $v);
                    break;
                case 'proxyport':
                    $this->setCurlOption(CURLOPT_PROXYPORT, $v);
                    break;
                default:
                    if (is_array($v) && isset($this->config[$option]) && is_array($this->config[$option])) {
                        $v = ArrayUtils::merge($this->config[$option], $v);
                    }
                    $this->config[$option] = $v;
                    break;
            }
        }
        return $this;
    }
    public function getConfig()
    {
        return $this->config;
    }
    public function setCurlOption($option, $value)
    {
        if (! isset($this->config['curloptions'])) {
            $this->config['curloptions'] = [];
        }
        $this->config['curloptions'][$option] = $value;
        return $this;
    }
    public function connect($host, $port = 80, $secure = false)
    {
                if ($this->curl) {
            $this->close();
        }
                $this->curl = curl_init();
        if ($port != 80) {
            curl_setopt($this->curl, CURLOPT_PORT, intval($port));
        }
        if (isset($this->config['connecttimeout'])) {
            $connectTimeout = $this->config['connecttimeout'];
        } elseif (isset($this->config['timeout'])) {
            $connectTimeout = $this->config['timeout'];
        } else {
            $connectTimeout = null;
        }
        if ($connectTimeout !== null) {
            if (defined('CURLOPT_CONNECTTIMEOUT_MS')) {
                curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeout * 1000);
            } else {
                curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
            }
        }
        if (isset($this->config['timeout'])) {
            if (defined('CURLOPT_TIMEOUT_MS')) {
                curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $this->config['timeout'] * 1000);
            } else {
                curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->config['timeout']);
            }
        }
        if (isset($this->config['sslcafile']) && $this->config['sslcafile']) {
            curl_setopt($this->curl, CURLOPT_CAINFO, $this->config['sslcafile']);
        }
        if (isset($this->config['sslcapath']) && $this->config['sslcapath']) {
            curl_setopt($this->curl, CURLOPT_CAPATH, $this->config['sslcapath']);
        }
        if (isset($this->config['maxredirects'])) {
                        curl_setopt($this->curl, CURLOPT_MAXREDIRS, $this->config['maxredirects']);
        }
        if (! $this->curl) {
            $this->close();
            throw new AdapterException\RuntimeException('Unable to Connect to ' . $host . ':' . $port);
        }
        if ($secure !== false) {
                        if (isset($this->config['sslcert'])) {
                curl_setopt($this->curl, CURLOPT_SSLCERT, $this->config['sslcert']);
            }
            if (isset($this->config['sslpassphrase'])) {
                curl_setopt($this->curl, CURLOPT_SSLCERTPASSWD, $this->config['sslpassphrase']);
            }
        }
                $this->connectedTo = [$host, $port];
    }
    public function write($method, $uri, $httpVersion = 1.1, $headers = [], $body = '')
    {
                if (! $this->curl) {
            throw new AdapterException\RuntimeException('Trying to write but we are not connected');
        }
        if ($this->connectedTo[0] != $uri->getHost() || $this->connectedTo[1] != $uri->getPort()) {
            throw new AdapterException\RuntimeException('Trying to write but we are connected to the wrong host');
        }
                curl_setopt($this->curl, CURLOPT_URL, $uri->__toString());
                $curlValue = true;
        switch ($method) {
            case 'GET':
                $curlMethod = CURLOPT_HTTPGET;
                break;
            case 'POST':
                $curlMethod = CURLOPT_POST;
                break;
            case 'PUT':
                                                if (is_resource($body)) {
                    $this->config['curloptions'][CURLOPT_INFILE] = $body;
                }
                if (isset($this->config['curloptions'][CURLOPT_INFILE])) {
                                                            if (! isset($headers['Content-Length'])
                        && ! isset($this->config['curloptions'][CURLOPT_INFILESIZE])
                    ) {
                        throw new AdapterException\RuntimeException(
                            'Cannot set a file-handle for cURL option CURLOPT_INFILE'
                            . ' without also setting its size in CURLOPT_INFILESIZE.'
                        );
                    }
                    if (isset($headers['Content-Length'])) {
                        $this->config['curloptions'][CURLOPT_INFILESIZE] = (int) $headers['Content-Length'];
                        unset($headers['Content-Length']);
                    }
                    if (is_resource($body)) {
                        $body = '';
                    }
                    $curlMethod = CURLOPT_UPLOAD;
                } else {
                    $curlMethod = CURLOPT_CUSTOMREQUEST;
                    $curlValue = 'PUT';
                }
                break;
            case 'PATCH':
                $curlMethod = CURLOPT_CUSTOMREQUEST;
                $curlValue = 'PATCH';
                break;
            case 'DELETE':
                $curlMethod = CURLOPT_CUSTOMREQUEST;
                $curlValue = 'DELETE';
                break;
            case 'OPTIONS':
                $curlMethod = CURLOPT_CUSTOMREQUEST;
                $curlValue = 'OPTIONS';
                break;
            case 'TRACE':
                $curlMethod = CURLOPT_CUSTOMREQUEST;
                $curlValue = 'TRACE';
                break;
            case 'HEAD':
                $curlMethod = CURLOPT_CUSTOMREQUEST;
                $curlValue = 'HEAD';
                break;
            default:
                                throw new AdapterException\InvalidArgumentException(sprintf(
                    'Method \'%s\' currently not supported',
                    $method
                ));
        }
        if (is_resource($body) && $curlMethod != CURLOPT_UPLOAD) {
            throw new AdapterException\RuntimeException('Streaming requests are allowed only with PUT');
        }
                $curlHttp = $httpVersion == 1.1 ? CURL_HTTP_VERSION_1_1 : CURL_HTTP_VERSION_1_0;
                curl_setopt($this->curl, CURLOPT_HTTP_VERSION, $curlHttp);
        curl_setopt($this->curl, $curlMethod, $curlValue);
                curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
        if ($this->outputStream) {
                        curl_setopt($this->curl, CURLOPT_HEADER, false);
            curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, [$this, 'readHeader']);
                        curl_setopt($this->curl, CURLOPT_FILE, $this->outputStream);
        } else {
                        curl_setopt($this->curl, CURLOPT_HEADER, true);
                        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        }
                if (array_key_exists('Authorization', $headers) && 'Basic' == substr($headers['Authorization'], 0, 5)) {
            curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($this->curl, CURLOPT_USERPWD, base64_decode(substr($headers['Authorization'], 6)));
            unset($headers['Authorization']);
        }
                if (! isset($headers['Accept'])) {
            $headers['Accept'] = '';
        }
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = $key . ': ' . $value;
        }
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $curlHeaders);
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], true)) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
        } elseif ($curlMethod == CURLOPT_UPLOAD) {
                                                curl_setopt($this->curl, CURLOPT_INFILE, $this->config['curloptions'][CURLOPT_INFILE]);
            curl_setopt($this->curl, CURLOPT_INFILESIZE, $this->config['curloptions'][CURLOPT_INFILESIZE]);
            unset($this->config['curloptions'][CURLOPT_INFILE]);
            unset($this->config['curloptions'][CURLOPT_INFILESIZE]);
        }
                if (isset($this->config['curloptions'])) {
            foreach ((array) $this->config['curloptions'] as $k => $v) {
                if (! in_array($k, $this->invalidOverwritableCurlOptions)) {
                    if (curl_setopt($this->curl, $k, $v) == false) {
                        throw new AdapterException\RuntimeException(sprintf(
                            'Unknown or erroreous cURL option "%s" set',
                            $k
                        ));
                    }
                }
            }
        }
        $this->response = '';
        $response = curl_exec($this->curl);
                if (! is_resource($this->outputStream)) {
            $this->response = $response;
        }
        $request  = curl_getinfo($this->curl, CURLINFO_HEADER_OUT);
        $request .= $body;
        if ($response === false || empty($this->response)) {
            if (curl_errno($this->curl) === static::ERROR_OPERATION_TIMEDOUT) {
                throw new AdapterException\TimeoutException(
                    'Read timed out',
                    AdapterException\TimeoutException::READ_TIMEOUT
                );
            }
            throw new AdapterException\RuntimeException(sprintf(
                'Error in cURL request: %s',
                curl_error($this->curl)
            ));
        }
                $responseHeaderSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $responseHeaders = substr($this->response, 0, $responseHeaderSize);
                        $responseHeaders = preg_replace("/Transfer-Encoding:\s*chunked\\r\\n/i", '', $responseHeaders);
                if (isset($this->config['curloptions'][CURLOPT_ENCODING])
            && '' == $this->config['curloptions'][CURLOPT_ENCODING]
        ) {
            $responseHeaders = preg_replace("/Content-Encoding:\s*gzip\\r\\n/i", '', $responseHeaders);
        }
                $responseHeaders = preg_replace(
            "/HTTP\/1.0\s*200\s*Connection\s*established\\r\\n\\r\\n/",
            '',
            $responseHeaders
        );
                $this->response = substr_replace($this->response, $responseHeaders, 0, $responseHeaderSize);
                do {
            $parts = preg_split('|(?:\r?\n){2}|m', $this->response, 2);
            $again = false;
            if (isset($parts[1]) && preg_match("|^HTTP/1\.[01](.*?)\r\n|mi", $parts[1])) {
                $this->response = $parts[1];
                $again          = true;
            }
        } while ($again);
        return $request;
    }
    public function read()
    {
        return $this->response;
    }
    public function close()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
        $this->curl         = null;
        $this->connectedTo = [null, null];
    }
    public function getHandle()
    {
        return $this->curl;
    }
    public function setOutputStream($stream)
    {
        $this->outputStream = $stream;
        return $this;
    }
    public function readHeader($curl, $header)
    {
        $this->response .= $header;
        return strlen($header);
    }
}
