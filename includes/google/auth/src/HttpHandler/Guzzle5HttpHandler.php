<?php
namespace Google\Auth\HttpHandler;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\ResponseInterface as Guzzle5ResponseInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
class Guzzle5HttpHandler
{
    private $client;
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }
    public function __invoke(RequestInterface $request, array $options = [])
    {
        $response = $this->client->send(
            $this->createGuzzle5Request($request, $options)
        );
        return $this->createPsr7Response($response);
    }
    public function async(RequestInterface $request, array $options = [])
    {
        if (!class_exists('GuzzleHttp\Promise\Promise')) {
            throw new Exception('Install guzzlehttp/promises to use async with Guzzle 5');
        }
        $futureResponse = $this->client->send(
            $this->createGuzzle5Request(
                $request,
                ['future' => true] + $options
            )
        );
        $promise = new Promise(
            function () use ($futureResponse) {
                try {
                    $futureResponse->wait();
                } catch (Exception $e) {
                }
            },
            [$futureResponse, 'cancel']
        );
        $futureResponse->then([$promise, 'resolve'], [$promise, 'reject']);
        return $promise->then(
            function (Guzzle5ResponseInterface $response) {
                return $this->createPsr7Response($response);
            },
            function (Exception $e) {
                return new RejectedPromise($e);
            }
        );
    }
    private function createGuzzle5Request(RequestInterface $request, array $options)
    {
        return $this->client->createRequest(
            $request->getMethod(),
            $request->getUri(),
            array_merge_recursive([
                'headers' => $request->getHeaders(),
                'body' => $request->getBody(),
            ], $options)
        );
    }
    private function createPsr7Response(Guzzle5ResponseInterface $response)
    {
        return new Response(
            $response->getStatusCode(),
            $response->getHeaders() ?: [],
            $response->getBody(),
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
    }
}
