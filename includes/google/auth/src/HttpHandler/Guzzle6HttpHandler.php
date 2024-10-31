<?php
namespace Google\Auth\HttpHandler;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
class Guzzle6HttpHandler
{
    private $client;
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }
    public function __invoke(RequestInterface $request, array $options = [])
    {
        return $this->client->send($request, $options);
    }
    public function async(RequestInterface $request, array $options = [])
    {
        return $this->client->sendAsync($request, $options);
    }
}
