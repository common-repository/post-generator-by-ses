<?php
namespace Google\Auth\HttpHandler;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
class HttpHandlerFactory
{
    public static function build(ClientInterface $client = null)
    {
        $version = ClientInterface::VERSION;
        $client = $client ?: new Client();
        switch ($version[0]) {
            case '5':
                return new Guzzle5HttpHandler($client);
            case '6':
                return new Guzzle6HttpHandler($client);
            default:
                throw new \Exception('Version not supported');
        }
    }
}
