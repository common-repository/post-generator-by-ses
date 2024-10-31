<?php
namespace DTS\eBaySDK;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
class HttpHandler
{
    private $client;
    private static $validOptions = [
        'connect_timeout' => true,
        'curl'            => true,
        'debug'           => true,
        'delay'           => true,
        'http_errors'     => true,
        'proxy'           => true,
        'timeout'         => true,
        'verify'          => true
    ];
    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client ?: new Client();
    }
    public function __invoke(RequestInterface $request, array $options)
    {
                foreach (array_keys($options) as $key) {
            if (!isset(self::$validOptions[$key])) {
                unset($options[$key]);
            }
        }
        return $this->client->sendAsync($request, $options);
    }
}
