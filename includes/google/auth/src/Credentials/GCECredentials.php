<?php
namespace Google\Auth\Credentials;
use Google\Auth\CredentialsLoader;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
class GCECredentials extends CredentialsLoader
{
    const cacheKey = 'GOOGLE_AUTH_PHP_GCE';
    const METADATA_IP = '169.254.169.254';
    const TOKEN_URI_PATH = 'v1/instance/service-accounts/default/token';
    const FLAVOR_HEADER = 'Metadata-Flavor';
    const MAX_COMPUTE_PING_TRIES = 3;
    const COMPUTE_PING_CONNECTION_TIMEOUT_S = 0.5;
    private $hasCheckedOnGce = false;
    private $isOnGce = false;
    protected $lastReceivedToken;
    public static function getTokenUri()
    {
        $base = 'http://' . self::METADATA_IP . '/computeMetadata/';
        return $base . self::TOKEN_URI_PATH;
    }
    public static function onAppEngineFlexible()
    {
        return substr(getenv('GAE_INSTANCE'), 0, 4) === 'aef-';
    }
    public static function onGce(callable $httpHandler = null)
    {
        if (is_null($httpHandler)) {
            $httpHandler = HttpHandlerFactory::build();
        }
        $checkUri = 'http://' . self::METADATA_IP;
        for ($i = 1; $i <= self::MAX_COMPUTE_PING_TRIES; $i++) {
            try {
                $resp = $httpHandler(
                    new Request('GET', $checkUri),
                    ['timeout' => self::COMPUTE_PING_CONNECTION_TIMEOUT_S]
                );
                return $resp->getHeaderLine(self::FLAVOR_HEADER) == 'Google';
            } catch (ClientException $e) {
            } catch (ServerException $e) {
            } catch (RequestException $e) {
            }
            $httpHandler = HttpHandlerFactory::build();
        }
        return false;
    }
    public function fetchAuthToken(callable $httpHandler = null)
    {
        if (is_null($httpHandler)) {
            $httpHandler = HttpHandlerFactory::build();
        }
        if (!$this->hasCheckedOnGce) {
            $this->isOnGce = self::onGce($httpHandler);
        }
        if (!$this->isOnGce) {
            return [];
        }
        $resp = $httpHandler(
            new Request(
                'GET',
                self::getTokenUri(),
                [self::FLAVOR_HEADER => 'Google']
            )
        );
        $body = (string) $resp->getBody();
        if (null === $json = json_decode($body, true)) {
            throw new \Exception('Invalid JSON response');
        }
        $this->lastReceivedToken = $json;
        $this->lastReceivedToken['expires_at'] = time() + $json['expires_in'];
        return $json;
    }
    public function getCacheKey()
    {
        return self::cacheKey;
    }
    public function getLastReceivedToken()
    {
        if ($this->lastReceivedToken) {
            return [
                'access_token' => $this->lastReceivedToken['access_token'],
                'expires_at' => $this->lastReceivedToken['expires_at'],
            ];
        }
        return null;
    }
}
