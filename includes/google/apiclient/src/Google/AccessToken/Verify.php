<?php
use Firebase\JWT\ExpiredException as ExpiredExceptionV3;
use Firebase\JWT\SignatureInvalidException;
use Google\Auth\Cache\MemoryCacheItemPool;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Cache\CacheItemPoolInterface;
class Google_AccessToken_Verify
{
    const FEDERATED_SIGNON_CERT_URL = 'https://www.googleapis.com/oauth2/v3/certs';
    const OAUTH2_ISSUER = 'accounts.google.com';
    const OAUTH2_ISSUER_HTTPS = 'https://accounts.google.com';
    private $http;
    private $cache;
    public function __construct(
      ClientInterface $http = null,
      CacheItemPoolInterface $cache = null,
      $jwt = null
  ) {
        if (null === $http) {
            $http = new Client();
        }
        if (null === $cache) {
            $cache = new MemoryCacheItemPool();
        }
        $this->http = $http;
        $this->cache = $cache;
        $this->jwt = $jwt ?: $this->getJwtService();
    }
    public function verifyIdToken($idToken, $audience = null)
    {
        if (empty($idToken)) {
            throw new LogicException('id_token cannot be null');
        }
        $this->setPhpsecConstants();
        $certs = $this->getFederatedSignOnCerts();
        foreach ($certs as $cert) {
            $bigIntClass = $this->getBigIntClass();
            $rsaClass = $this->getRsaClass();
            $modulus = new $bigIntClass($this->jwt->urlsafeB64Decode($cert['n']), 256);
            $exponent = new $bigIntClass($this->jwt->urlsafeB64Decode($cert['e']), 256);
            $rsa = new $rsaClass();
            $rsa->loadKey(['n' => $modulus, 'e' => $exponent]);
            try {
                $payload = $this->jwt->decode(
            $idToken,
            $rsa->getPublicKey(),
            ['RS256']
        );
                if (property_exists($payload, 'aud')) {
                    if ($audience && $payload->aud != $audience) {
                        return false;
                    }
                }
                $issuers = [self::OAUTH2_ISSUER, self::OAUTH2_ISSUER_HTTPS];
                if (!isset($payload->iss) || !in_array($payload->iss, $issuers)) {
                    return false;
                }
                return (array) $payload;
            } catch (ExpiredException $e) {
                return false;
            } catch (ExpiredExceptionV3 $e) {
                return false;
            } catch (SignatureInvalidException $e) {
            } catch (DomainException $e) {
            }
        }
        return false;
    }
    private function getCache()
    {
        return $this->cache;
    }
    private function retrieveCertsFromLocation($url)
    {
        if (0 !== strpos($url, 'http')) {
            if (!$file = file_get_contents($url)) {
                throw new Google_Exception(
            "Failed to retrieve verification certificates: '" .
            $url . "'."
        );
            }
            return json_decode($file, true);
        }
        $response = $this->http->get($url);
        if ($response->getStatusCode() == 200) {
            return json_decode((string) $response->getBody(), true);
        }
        throw new Google_Exception(
        sprintf(
            'Failed to retrieve verification certificates: "%s".',
            $response->getBody()->getContents()
        ),
        $response->getStatusCode()
    );
    }
    private function getFederatedSignOnCerts()
    {
        $certs = null;
        if ($cache = $this->getCache()) {
            $cacheItem = $cache->getItem('federated_signon_certs_v3');
            $certs = $cacheItem->get();
        }
        if (!$certs) {
            $certs = $this->retrieveCertsFromLocation(
          self::FEDERATED_SIGNON_CERT_URL
      );
            if ($cache) {
                $cacheItem->expiresAt(new DateTime('+1 hour'));
                $cacheItem->set($certs);
                $cache->save($cacheItem);
            }
        }
        if (!isset($certs['keys'])) {
            throw new InvalidArgumentException(
          'federated sign-on certs expects "keys" to be set'
      );
        }
        return $certs['keys'];
    }
    private function getJwtService()
    {
        $jwtClass = 'JWT';
        if (class_exists('\Firebase\JWT\JWT')) {
            $jwtClass = 'Firebase\JWT\JWT';
        }
        if (property_exists($jwtClass, 'leeway') && $jwtClass::$leeway < 1) {
            $jwtClass::$leeway = 1;
        }
        return new $jwtClass();
    }
    private function getRsaClass()
    {
        if (class_exists('phpseclib\Crypt\RSA')) {
            return 'phpseclib\Crypt\RSA';
        }
        return 'Crypt_RSA';
    }
    private function getBigIntClass()
    {
        if (class_exists('phpseclib\Math\BigInteger')) {
            return 'phpseclib\Math\BigInteger';
        }
        return 'Math_BigInteger';
    }
    private function getOpenSslConstant()
    {
        if (class_exists('phpseclib\Crypt\RSA')) {
            return 'phpseclib\Crypt\RSA::MODE_OPENSSL';
        }
        if (class_exists('Crypt_RSA')) {
            return 'CRYPT_RSA_MODE_OPENSSL';
        }
        throw new \Exception('Cannot find RSA class');
    }
    private function setPhpsecConstants()
    {
        if (filter_var(getenv('GAE_VM'), FILTER_VALIDATE_BOOLEAN)) {
            if (!defined('MATH_BIGINTEGER_OPENSSL_ENABLED')) {
                define('MATH_BIGINTEGER_OPENSSL_ENABLED', true);
            }
            if (!defined('CRYPT_RSA_MODE')) {
                define('CRYPT_RSA_MODE', constant($this->getOpenSslConstant()));
            }
        }
    }
}
