<?php
namespace Google\Auth\Credentials;
use google\appengine\api\app_identity\AppIdentityService;
use Google\Auth\CredentialsLoader;
class AppIdentityCredentials extends CredentialsLoader
{
    protected $lastReceivedToken;
    private $scope;
    public function __construct($scope = [])
    {
        $this->scope = $scope;
    }
    public static function onAppEngine()
    {
        $appEngineProduction = isset($_SERVER['SERVER_SOFTWARE']) &&
            0 === strpos($_SERVER['SERVER_SOFTWARE'], 'Google App Engine');
        if ($appEngineProduction) {
            return true;
        }
        $appEngineDevAppServer = isset($_SERVER['APPENGINE_RUNTIME']) &&
            $_SERVER['APPENGINE_RUNTIME'] == 'php';
        if ($appEngineDevAppServer) {
            return true;
        }
        return false;
    }
    public function fetchAuthToken(callable $httpHandler = null)
    {
        if (!self::onAppEngine()) {
            return [];
        }
        if (!class_exists('google\appengine\api\app_identity\AppIdentityService')) {
            throw new \Exception(
                'This class must be run in App Engine, or you must include the AppIdentityService '
                . 'mock class defined in tests/mocks/AppIdentityService.php'
            );
        }
        $scope = is_array($this->scope) ? $this->scope : explode(' ', $this->scope);
        $token = AppIdentityService::getAccessToken($scope);
        $this->lastReceivedToken = $token;
        return $token;
    }
    public function getLastReceivedToken()
    {
        if ($this->lastReceivedToken) {
            return [
                'access_token' => $this->lastReceivedToken['access_token'],
                'expires_at' => $this->lastReceivedToken['expiration_time'],
            ];
        }
        return null;
    }
    public function getCacheKey()
    {
        return '';
    }
}
