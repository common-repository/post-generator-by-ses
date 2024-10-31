<?php
namespace Google\Auth\Credentials;
use Google\Auth\FetchAuthTokenInterface;
class InsecureCredentials implements FetchAuthTokenInterface
{
    private $token = [
        'access_token' => ''
    ];
    public function fetchAuthToken(callable $httpHandler = null)
    {
        return $this->token;
    }
    public function getCacheKey()
    {
        return null;
    }
    public function getLastReceivedToken()
    {
        return $this->token;
    }
}
