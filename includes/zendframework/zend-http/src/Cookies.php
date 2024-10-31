<?php
namespace Zend\Http;
use ArrayIterator;
use Zend\Http\Header\SetCookie;
use Zend\Uri;
class Cookies extends Headers
{
    const COOKIE_OBJECT = 0;
    const COOKIE_STRING_ARRAY = 1;
    const COOKIE_STRING_CONCAT = 2;
    const COOKIE_STRING_CONCAT_STRICT = 3;
    protected $cookies = [];
    protected $headers;
    protected $rawCookies;
    public static function fromString($string)
    {
        throw new Exception\RuntimeException(
            __CLASS__ . '::' . __FUNCTION__ . ' should not be used as a factory, use '
            . __NAMESPACE__ . '\Headers::fromtString() instead.'
        );
    }
    public function addCookie($cookie, $refUri = null)
    {
        if (is_string($cookie)) {
            $cookie = SetCookie::fromString($cookie, $refUri);
        }
        if ($cookie instanceof SetCookie) {
            $domain = $cookie->getDomain();
            $path   = $cookie->getPath();
            if (! isset($this->cookies[$domain])) {
                $this->cookies[$domain] = [];
            }
            if (! isset($this->cookies[$domain][$path])) {
                $this->cookies[$domain][$path] = [];
            }
            $this->cookies[$domain][$path][$cookie->getName()] = $cookie;
            $this->rawCookies[] = $cookie;
        } else {
            throw new Exception\InvalidArgumentException('Supplient argument is not a valid cookie string or object');
        }
    }
    public function addCookiesFromResponse(Response $response, $refUri)
    {
        $cookieHdrs = $response->getHeaders()->get('Set-Cookie');
        if (is_array($cookieHdrs) || $cookieHdrs instanceof ArrayIterator) {
            foreach ($cookieHdrs as $cookie) {
                $this->addCookie($cookie, $refUri);
            }
        } elseif (is_string($cookieHdrs)) {
            $this->addCookie($cookieHdrs, $refUri);
        }
    }
    public function getAllCookies($retAs = self::COOKIE_OBJECT)
    {
        $cookies = $this->_flattenCookiesArray($this->cookies, $retAs);
        return $cookies;
    }
    public function getMatchingCookies(
        $uri,
        $matchSessionCookies = true,
        $retAs = self::COOKIE_OBJECT,
        $now = null
    ) {
        if (is_string($uri)) {
            $uri = Uri\UriFactory::factory($uri, 'http');
        } elseif (! $uri instanceof Uri\Uri) {
            throw new Exception\InvalidArgumentException('Invalid URI string or object passed');
        }
        $host = $uri->getHost();
        if (empty($host)) {
            throw new Exception\InvalidArgumentException('Invalid URI specified; does not contain a host');
        }
                $cookies = $this->_matchDomain($host);
        $cookies = $this->_matchPath($cookies, $uri->getPath());
        $cookies = $this->_flattenCookiesArray($cookies, self::COOKIE_OBJECT);
                $ret = [];
        foreach ($cookies as $cookie) {
            if ($cookie->match($uri, $matchSessionCookies, $now)) {
                $ret[] = $cookie;
            }
        }
                $ret = $this->_flattenCookiesArray($ret, $retAs);
        return $ret;
    }
    public function getCookie($uri, $cookieName, $retAs = self::COOKIE_OBJECT)
    {
        if (is_string($uri)) {
            $uri = Uri\UriFactory::factory($uri, 'http');
        } elseif (! $uri instanceof Uri\Uri) {
            throw new Exception\InvalidArgumentException('Invalid URI specified');
        }
        $host = $uri->getHost();
        if (empty($host)) {
            throw new Exception\InvalidArgumentException('Invalid URI specified; host missing');
        }
                $path = $uri->getPath();
        $lastSlashPos = strrpos($path, '/') ?: 0;
        $path = substr($path, 0, $lastSlashPos);
        if (! $path) {
            $path = '/';
        }
        if (isset($this->cookies[$uri->getHost()][$path][$cookieName])) {
            $cookie = $this->cookies[$uri->getHost()][$path][$cookieName];
            switch ($retAs) {
                case self::COOKIE_OBJECT:
                    return $cookie;
                case self::COOKIE_STRING_ARRAY:
                case self::COOKIE_STRING_CONCAT:
                    return $cookie->__toString();
                default:
                    throw new Exception\InvalidArgumentException(sprintf(
                        'Invalid value passed for $retAs: %s',
                        $retAs
                    ));
            }
        }
        return false;
    }
        protected function _flattenCookiesArray($ptr, $retAs = self::COOKIE_OBJECT)
    {
                if (is_array($ptr)) {
            $ret = ($retAs == self::COOKIE_STRING_CONCAT ? '' : []);
            foreach ($ptr as $item) {
                if ($retAs == self::COOKIE_STRING_CONCAT) {
                    $ret .= $this->_flattenCookiesArray($item, $retAs);
                } else {
                    $ret = array_merge($ret, $this->_flattenCookiesArray($item, $retAs));
                }
            }
            return $ret;
        } elseif ($ptr instanceof SetCookie) {
            switch ($retAs) {
                case self::COOKIE_STRING_ARRAY:
                    return [$ptr->__toString()];
                case self::COOKIE_STRING_CONCAT:
                    return $ptr->__toString();
                case self::COOKIE_OBJECT:
                default:
                    return [$ptr];
            }
        }
        return;
    }
        protected function _matchDomain($domain)
    {
                $ret = [];
        foreach (array_keys($this->cookies) as $cdom) {
            if (SetCookie::matchCookieDomain($cdom, $domain)) {
                $ret[$cdom] = $this->cookies[$cdom];
            }
        }
        return $ret;
    }
        protected function _matchPath($domains, $path)
    {
                $ret = [];
        foreach ($domains as $dom => $pathsArray) {
            foreach (array_keys($pathsArray) as $cpath) {
                if (SetCookie::matchCookiePath($cpath, $path)) {
                    if (! isset($ret[$dom])) {
                        $ret[$dom] = [];
                    }
                    $ret[$dom][$cpath] = $pathsArray[$cpath];
                }
            }
        }
        return $ret;
    }
    public static function fromResponse(Response $response, $refUri)
    {
        $jar = new static();
        $jar->addCookiesFromResponse($response, $refUri);
        return $jar;
    }
    public function isEmpty()
    {
        return count($this) == 0;
    }
    public function reset()
    {
        $this->cookies = $this->rawCookies = [];
        return $this;
    }
}
