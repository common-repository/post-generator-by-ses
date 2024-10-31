<?php
namespace Zend\Http\Client\Adapter;
use Zend\Http\Client;
use Zend\Http\Client\Adapter\Exception as AdapterException;
use Zend\Http\Response;
use Zend\Stdlib\ErrorHandler;
class Proxy extends Socket
{
    protected $config = [
        'persistent'         => false,
        'ssltransport'       => 'ssl',
        'sslcert'            => null,
        'sslpassphrase'      => null,
        'sslverifypeer'      => true,
        'sslcafile'          => null,
        'sslcapath'          => null,
        'sslallowselfsigned' => false,
        'sslusecontext'      => false,
        'sslverifypeername'  => true,
        'proxy_host'         => '',
        'proxy_port'         => 8080,
        'proxy_user'         => '',
        'proxy_pass'         => '',
        'proxy_auth'         => Client::AUTH_BASIC,
    ];
    protected $negotiated = false;
    public function setOptions($options = [])
    {
                foreach ($options as $k => $v) {
            if (preg_match('/^proxy[a-z]+/', $k)) {
                $options['proxy_' . substr($k, 5, strlen($k))] = $v;
                unset($options[$k]);
            }
        }
        parent::setOptions($options);
    }
    public function connect($host, $port = 80, $secure = false)
    {
                if (! $this->config['proxy_host']) {
            parent::connect($host, $port, $secure);
            return;
        }
        if ($secure) {
            $this->config['sslusecontext'] = true;
        }
                parent::connect(
            $this->config['proxy_host'],
            $this->config['proxy_port'],
            false
        );
    }
    public function write($method, $uri, $httpVer = '1.1', $headers = [], $body = '')
    {
                if (! $this->config['proxy_host']) {
            return parent::write($method, $uri, $httpVer, $headers, $body);
        }
                if (! $this->socket) {
            throw new AdapterException\RuntimeException('Trying to write but we are not connected');
        }
        $host = $this->config['proxy_host'];
        $port = $this->config['proxy_port'];
        if ($this->connectedTo[0] != sprintf('tcp://%s', $host) || $this->connectedTo[1] != $port) {
            throw new AdapterException\RuntimeException(
                'Trying to write but we are connected to the wrong proxy server'
            );
        }
                if ($this->config['proxy_user'] && ! isset($headers['proxy-authorization'])) {
            $headers['proxy-authorization'] = Client::encodeAuthHeader(
                $this->config['proxy_user'],
                $this->config['proxy_pass'],
                $this->config['proxy_auth']
            );
        }
                if ($uri->getScheme() == 'https' && ! $this->negotiated) {
            $this->connectHandshake($uri->getHost(), $uri->getPort(), $httpVer, $headers);
            $this->negotiated = true;
        }
                $this->method = $method;
                if ($this->negotiated) {
            $path = $uri->getPath();
            $query = $uri->getQuery();
            $path .= $query ? '?' . $query : '';
            $request = sprintf('%s %s HTTP/%s%s', $method, $path, $httpVer, "\r\n");
        } else {
            $request = sprintf('%s %s HTTP/%s%s', $method, $uri, $httpVer, "\r\n");
        }
                foreach ($headers as $k => $v) {
            if (is_string($k)) {
                $v = $k . ': ' . $v;
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
        if (! $test) {
            throw new AdapterException\RuntimeException('Error writing request to proxy server', 0, $error);
        }
        if (is_resource($body)) {
            if (stream_copy_to_stream($body, $this->socket) == 0) {
                throw new AdapterException\RuntimeException('Error writing request to server');
            }
        }
        return $request;
    }
    protected function connectHandshake($host, $port = 443, $httpVer = '1.1', array &$headers = [])
    {
        $request = 'CONNECT ' . $host . ':' . $port . ' HTTP/' . $httpVer . "\r\n"
            . 'Host: ' . $host . "\r\n";
                if (isset($this->config['useragent'])) {
            $request .= 'User-agent: ' . $this->config['useragent'] . "\r\n";
        }
                        if (isset($headers['proxy-authorization'])) {
            $request .= 'Proxy-authorization: ' . $headers['proxy-authorization'] . "\r\n";
            unset($headers['proxy-authorization']);
        }
        $request .= "\r\n";
                ErrorHandler::start();
        $test  = fwrite($this->socket, $request);
        $error = ErrorHandler::stop();
        if (! $test) {
            throw new AdapterException\RuntimeException('Error writing request to proxy server', 0, $error);
        }
                $response = '';
        $gotStatus = false;
        ErrorHandler::start();
        while ($line = fgets($this->socket)) {
            $gotStatus = $gotStatus || (strpos($line, 'HTTP') !== false);
            if ($gotStatus) {
                $response .= $line;
                if (! rtrim($line)) {
                    break;
                }
            }
        }
        ErrorHandler::stop();
                if (Response::fromString($response)->getStatusCode() != 200) {
            throw new AdapterException\RuntimeException(sprintf(
                'Unable to connect to HTTPS proxy. Server response: %s',
                $response
            ));
        }
                        $modes = [
            STREAM_CRYPTO_METHOD_TLS_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv2_CLIENT,
        ];
        $success = false;
        foreach ($modes as $mode) {
            $success = stream_socket_enable_crypto($this->socket, true, $mode);
            if ($success) {
                break;
            }
        }
        if (! $success) {
            throw new AdapterException\RuntimeException(
                'Unable to connect to HTTPS server through proxy: could not negotiate secure connection.'
            );
        }
    }
    public function close()
    {
        parent::close();
        $this->negotiated = false;
    }
    public function __destruct()
    {
        if ($this->socket) {
            $this->close();
        }
    }
}
