<?php
namespace Google\Auth\Subscriber;
use Google\Auth\FetchAuthTokenInterface;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;
class AuthTokenSubscriber implements SubscriberInterface
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
    public function getEvents()
    {
        return ['before' => ['onBefore', RequestEvents::SIGN_REQUEST]];
    }
    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();
        if ($request->getConfig()['auth'] != 'google_auth') {
            return;
        }
        $auth_tokens = $this->fetcher->fetchAuthToken($this->httpHandler);
        if (array_key_exists('access_token', $auth_tokens)) {
            $request->setHeader('authorization', 'Bearer ' . $auth_tokens['access_token']);
            if ($this->tokenCallback) {
                call_user_func($this->tokenCallback, $this->fetcher->getCacheKey(), $auth_tokens['access_token']);
            }
        }
    }
}
