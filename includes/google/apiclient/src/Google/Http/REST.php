<?php
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
class Google_Http_REST
{
    public static function execute(
      ClientInterface $client,
      RequestInterface $request,
      $expectedClass = null,
      $config = [],
      $retryMap = null
  ) {
        $runner = new Google_Task_Runner(
        $config,
        sprintf('%s %s', $request->getMethod(), (string) $request->getUri()),
        [get_class(), 'doExecute'],
        [$client, $request, $expectedClass]
    );
        if (null !== $retryMap) {
            $runner->setRetryMap($retryMap);
        }
        return $runner->run();
    }
    public static function doExecute(ClientInterface $client, RequestInterface $request, $expectedClass = null)
    {
        try {
            $httpHandler = HttpHandlerFactory::build($client);
            $response = $httpHandler($request);
        } catch (RequestException $e) {
            if (!$e->hasResponse()) {
                throw $e;
            }
            $response = $e->getResponse();
            if ($response instanceof \GuzzleHttp\Message\ResponseInterface) {
                $response = new Response(
            $response->getStatusCode(),
            $response->getHeaders() ?: [],
            $response->getBody(),
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
            }
        }
        return self::decodeHttpResponse($response, $request, $expectedClass);
    }
    public static function decodeHttpResponse(
      ResponseInterface $response,
      RequestInterface $request = null,
      $expectedClass = null
  ) {
        $code = $response->getStatusCode();
        if (intval($code) >= 400) {
            $body = (string) $response->getBody();
            throw new Google_Service_Exception($body, $code, null, self::getResponseErrors($body));
        }
        $body = self::decodeBody($response, $request);
        if ($expectedClass = self::determineExpectedClass($expectedClass, $request)) {
            $json = json_decode($body, true);
            return new $expectedClass($json);
        }
        return $response;
    }
    private static function decodeBody(ResponseInterface $response, RequestInterface $request = null)
    {
        if (self::isAltMedia($request)) {
            return '';
        }
        return (string) $response->getBody();
    }
    private static function determineExpectedClass($expectedClass, RequestInterface $request = null)
    {
        if (false === $expectedClass) {
            return null;
        }
        if (null === $request) {
            return $expectedClass;
        }
        return $expectedClass ?: $request->getHeaderLine('X-Php-Expected-Class');
    }
    private static function getResponseErrors($body)
    {
        $json = json_decode($body, true);
        if (isset($json['error']['errors'])) {
            return $json['error']['errors'];
        }
        return null;
    }
    private static function isAltMedia(RequestInterface $request = null)
    {
        if ($request && $qs = $request->getUri()->getQuery()) {
            parse_str($qs, $query);
            if (isset($query['alt']) && $query['alt'] == 'media') {
                return true;
            }
        }
        return false;
    }
}
