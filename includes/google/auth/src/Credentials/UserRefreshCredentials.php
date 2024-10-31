<?php
namespace Google\Auth\Credentials;
use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;
class UserRefreshCredentials extends CredentialsLoader
{
    const CLOUD_SDK_CLIENT_ID =
        '764086051850-6qr4p6gpi6hn506pt8ejuq83di341hur.apps.googleusercontent.com';
    const SUPPRESS_CLOUD_SDK_CREDS_WARNING_ENV = 'SUPPRESS_GCLOUD_CREDS_WARNING';
    protected $auth;
    public function __construct(
        $scope,
        $jsonKey
    ) {
        if (is_string($jsonKey)) {
            if (!file_exists($jsonKey)) {
                throw new \InvalidArgumentException('file does not exist');
            }
            $jsonKeyStream = file_get_contents($jsonKey);
            if (!$jsonKey = json_decode($jsonKeyStream, true)) {
                throw new \LogicException('invalid json for auth config');
            }
        }
        if (!array_key_exists('client_id', $jsonKey)) {
            throw new \InvalidArgumentException(
                'json key is missing the client_id field'
            );
        }
        if (!array_key_exists('client_secret', $jsonKey)) {
            throw new \InvalidArgumentException(
                'json key is missing the client_secret field'
            );
        }
        if (!array_key_exists('refresh_token', $jsonKey)) {
            throw new \InvalidArgumentException(
                'json key is missing the refresh_token field'
            );
        }
        $this->auth = new OAuth2([
            'clientId' => $jsonKey['client_id'],
            'clientSecret' => $jsonKey['client_secret'],
            'refresh_token' => $jsonKey['refresh_token'],
            'scope' => $scope,
            'tokenCredentialUri' => self::TOKEN_CREDENTIAL_URI,
        ]);
        if ($jsonKey['client_id'] === self::CLOUD_SDK_CLIENT_ID
            && getenv(self::SUPPRESS_CLOUD_SDK_CREDS_WARNING_ENV) !== 'true') {
            trigger_error(
                'Your application has authenticated using end user credentials '
                . 'from Google Cloud SDK. We recommend that most server '
                . 'applications use service accounts instead. If your '
                . 'application continues to use end user credentials '
                . 'from Cloud SDK, you might receive a "quota exceeded" '
                . 'or "API not enabled" error. For more information about '
                . 'service accounts, see '
                . 'https://cloud.google.com/docs/authentication/. '
                . 'To disable this warning, set '
                . self::SUPPRESS_CLOUD_SDK_CREDS_WARNING_ENV
                . ' environment variable to "true".',
                E_USER_WARNING
            );
        }
    }
    public function fetchAuthToken(callable $httpHandler = null)
    {
        return $this->auth->fetchAuthToken($httpHandler);
    }
    public function getCacheKey()
    {
        return $this->auth->getClientId() . ':' . $this->auth->getCacheKey();
    }
    public function getLastReceivedToken()
    {
        return $this->auth->getLastReceivedToken();
    }
}
