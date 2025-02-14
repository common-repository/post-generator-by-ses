<?php
namespace GuzzleHttp;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\Handler\StreamHandler;
function uri_template($template, array $variables)
{
    if (extension_loaded('uri_template')) {
        return \uri_template($template, $variables);
    }
    static $uriTemplate;
    if (!$uriTemplate) {
        $uriTemplate = new UriTemplate();
    }
    return $uriTemplate->expand($template, $variables);
}
function describe_type($input)
{
    switch (gettype($input)) {
        case 'object':
            return 'object(' . get_class($input) . ')';
        case 'array':
            return 'array(' . count($input) . ')';
        default:
            ob_start();
            var_dump($input);
                        return str_replace('double(', 'float(', rtrim(ob_get_clean()));
    }
}
function headers_from_lines($lines)
{
    $headers = [];
    foreach ($lines as $line) {
        $parts = explode(':', $line, 2);
        $headers[trim($parts[0])][] = isset($parts[1])
            ? trim($parts[1])
            : null;
    }
    return $headers;
}
function debug_resource($value = null)
{
    if (is_resource($value)) {
        return $value;
    } elseif (defined('STDOUT')) {
        return STDOUT;
    }
    return fopen('php://output', 'w');
}
function choose_handler()
{
    $handler = null;
    if (function_exists('curl_multi_exec') && function_exists('curl_exec')) {
        $handler = Proxy::wrapSync(new CurlMultiHandler(), new CurlHandler());
    } elseif (function_exists('curl_exec')) {
        $handler = new CurlHandler();
    } elseif (function_exists('curl_multi_exec')) {
        $handler = new CurlMultiHandler();
    }
    if (ini_get('allow_url_fopen')) {
        $handler = $handler
            ? Proxy::wrapStreaming($handler, new StreamHandler())
            : new StreamHandler();
    } elseif (!$handler) {
        throw new \RuntimeException('GuzzleHttp requires cURL, the '
            . 'allow_url_fopen ini setting, or a custom HTTP handler.');
    }
    return $handler;
}
function default_user_agent()
{
    static $defaultAgent = '';
    if (!$defaultAgent) {
        $defaultAgent = 'GuzzleHttp/' . Client::VERSION;
        if (extension_loaded('curl') && function_exists('curl_version')) {
            $defaultAgent .= ' curl/' . \curl_version()['version'];
        }
        $defaultAgent .= ' PHP/' . PHP_VERSION;
    }
    return $defaultAgent;
}
function default_ca_bundle()
{
    static $cached = null;
    static $cafiles = [
                '/etc/pki/tls/certs/ca-bundle.crt',
                '/etc/ssl/certs/ca-certificates.crt',
                '/usr/local/share/certs/ca-root-nss.crt',
                '/var/lib/ca-certificates/ca-bundle.pem',
                '/usr/local/etc/openssl/cert.pem',
                '/etc/ca-certificates.crt',
                'C:\\windows\\system32\\curl-ca-bundle.crt',
        'C:\\windows\\curl-ca-bundle.crt',
    ];
    if ($cached) {
        return $cached;
    }
    if ($ca = ini_get('openssl.cafile')) {
        return $cached = $ca;
    }
    if ($ca = ini_get('curl.cainfo')) {
        return $cached = $ca;
    }
    foreach ($cafiles as $filename) {
        if (file_exists($filename)) {
            return $cached = $filename;
        }
    }
    throw new \RuntimeException(
        <<< EOT
No system CA bundle could be found in any of the the common system locations.
PHP versions earlier than 5.6 are not properly configured to use the system's
CA bundle by default. In order to verify peer certificates, you will need to
supply the path on disk to a certificate bundle to the 'verify' request
option: http://docs.guzzlephp.org/en/latest/clients.html#verify. If you do not
need a specific certificate bundle, then Mozilla provides a commonly used CA
bundle which can be downloaded here (provided by the maintainer of cURL):
https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt. Once
you have a CA bundle available on disk, you can set the 'openssl.cafile' PHP
ini setting to point to the path to the file, allowing you to omit the 'verify'
request option. See http://curl.haxx.se/docs/sslcerts.html for more
information.
EOT
    );
}
function normalize_header_keys(array $headers)
{
    $result = [];
    foreach (array_keys($headers) as $key) {
        $result[strtolower($key)] = $key;
    }
    return $result;
}
function is_host_in_noproxy($host, array $noProxyArray)
{
    if (strlen($host) === 0) {
        throw new \InvalidArgumentException('Empty host provided');
    }
    if (strpos($host, ':')) {
        $host = explode($host, ':', 2)[0];
    }
    foreach ($noProxyArray as $area) {
        if ($area === '*') {
            return true;
        } elseif (empty($area)) {
            continue;
        } elseif ($area === $host) {
            return true;
        } else {
            $area = '.' . ltrim($area, '.');
            if (substr($host, -(strlen($area))) === $area) {
                return true;
            }
        }
    }
    return false;
}
function json_decode($json, $assoc = false, $depth = 512, $options = 0)
{
    $data = \json_decode($json, $assoc, $depth, $options);
    if (JSON_ERROR_NONE !== json_last_error()) {
        throw new \InvalidArgumentException(
            'json_decode error: ' . json_last_error_msg()
        );
    }
    return $data;
}
function json_encode($value, $options = 0, $depth = 512)
{
    $json = \json_encode($value, $options, $depth);
    if (JSON_ERROR_NONE !== json_last_error()) {
        throw new \InvalidArgumentException(
            'json_encode error: ' . json_last_error_msg()
        );
    }
    return $json;
}
