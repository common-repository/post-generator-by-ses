<?php
namespace Google\Auth\Middleware;
use Google\Auth\FetchAuthTokenInterface;
use Psr\Http\Message\RequestInterface;
class AuthTokenMiddleware
{
    private $httpHandler;
    private $fetcher;
    private $tokenCallback;
    public function __construct(
        FetchAuthTokenInterface $fetcher,
        callable $httpHandler = null,
        callable $tokenCallback = null
    ) {
        $this->fetcher = $fetcher;
        $this->httpHandler = $httpHandler;
        $this->tokenCallback = $tokenCallback;
    }
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            if (!isset($options['auth']) || $options['auth'] !== 'google_auth') {
                return $handler($request, $options);
            }
            $request = $request->withHeader('authorization', 'Bearer ' . $this->fetchToken());
            return $handler($request, $options);
        };
    }
    private function fetchToken()
    {
        $auth_tokens = $this->fetcher->fetchAuthToken($this->httpHandler);
        if (array_key_exists('access_token', $auth_tokens)) {
            if ($this->tokenCallback) {
                call_user_func($this->tokenCallback, $this->fetcher->getCacheKey(), $auth_tokens['access_token']);
            }
            return $auth_tokens['access_token'];
        }
    }
}
