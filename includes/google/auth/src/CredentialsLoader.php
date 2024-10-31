<?php
namespace Google\Auth;
use Google\Auth\Credentials\InsecureCredentials;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\Credentials\UserRefreshCredentials;
abstract class CredentialsLoader implements FetchAuthTokenInterface
{
    const TOKEN_CREDENTIAL_URI = 'https://oauth2.googleapis.com/token';
    const ENV_VAR = 'GOOGLE_APPLICATION_CREDENTIALS';
    const WELL_KNOWN_PATH = 'gcloud/application_default_credentials.json';
    const NON_WINDOWS_WELL_KNOWN_PATH_BASE = '.config';
    const AUTH_METADATA_KEY = 'authorization';
    private static function unableToReadEnv($cause)
    {
        $msg = 'Unable to read the credential file specified by ';
        $msg .= ' GOOGLE_APPLICATION_CREDENTIALS: ';
        $msg .= $cause;
        return $msg;
    }
    private static function isOnWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
    public static function fromEnv()
    {
        $path = getenv(self::ENV_VAR);
        if (empty($path)) {
            return;
        }
        if (!file_exists($path)) {
            $cause = 'file ' . $path . ' does not exist';
            throw new \DomainException(self::unableToReadEnv($cause));
        }
        $jsonKey = file_get_contents($path);
        return json_decode($jsonKey, true);
    }
    public static function fromWellKnownFile()
    {
        $rootEnv = self::isOnWindows() ? 'APPDATA' : 'HOME';
        $path = [getenv($rootEnv)];
        if (!self::isOnWindows()) {
            $path[] = self::NON_WINDOWS_WELL_KNOWN_PATH_BASE;
        }
        $path[] = self::WELL_KNOWN_PATH;
        $path = implode(DIRECTORY_SEPARATOR, $path);
        if (!file_exists($path)) {
            return;
        }
        $jsonKey = file_get_contents($path);
        return json_decode($jsonKey, true);
    }
    public static function makeCredentials($scope, array $jsonKey)
    {
        if (!array_key_exists('type', $jsonKey)) {
            throw new \InvalidArgumentException('json key is missing the type field');
        }
        if ($jsonKey['type'] == 'service_account') {
            return new ServiceAccountCredentials($scope, $jsonKey);
        }
        if ($jsonKey['type'] == 'authorized_user') {
            return new UserRefreshCredentials($scope, $jsonKey);
        }
        throw new \InvalidArgumentException('invalid value in the type field');
    }
    public static function makeHttpClient(
        FetchAuthTokenInterface $fetcher,
        array $httpClientOptions = [],
        callable $httpHandler = null,
        callable $tokenCallback = null
    ) {
        $version = \GuzzleHttp\ClientInterface::VERSION;
        switch ($version[0]) {
            case '5':
                $client = new \GuzzleHttp\Client($httpClientOptions);
                $client->setDefaultOption('auth', 'google_auth');
                $subscriber = new Subscriber\AuthTokenSubscriber(
                    $fetcher,
                    $httpHandler,
                    $tokenCallback
                );
                $client->getEmitter()->attach($subscriber);
                return $client;
            case '6':
                $middleware = new Middleware\AuthTokenMiddleware(
                    $fetcher,
                    $httpHandler,
                    $tokenCallback
                );
                $stack = \GuzzleHttp\HandlerStack::create();
                $stack->push($middleware);
                return new \GuzzleHttp\Client([
                   'handler' => $stack,
                   'auth' => 'google_auth',
                ] + $httpClientOptions);
            default:
                throw new \Exception('Version not supported');
        }
    }
    public static function makeInsecureCredentials()
    {
        return new InsecureCredentials();
    }
    public function getUpdateMetadataFunc()
    {
        return [$this, 'updateMetadata'];
    }
    public function updateMetadata(
        $metadata,
        $authUri = null,
        callable $httpHandler = null
    ) {
        $result = $this->fetchAuthToken($httpHandler);
        if (!isset($result['access_token'])) {
            return $metadata;
        }
        $metadata_copy = $metadata;
        $metadata_copy[self::AUTH_METADATA_KEY] = ['Bearer ' . $result['access_token']];
        return $metadata_copy;
    }
}
