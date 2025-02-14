<?php
namespace GuzzleHttp\Psr7;
use Psr\Http\Message\UriInterface;
class Uri implements UriInterface
{
    const HTTP_DEFAULT_HOST = 'localhost';
    private static $defaultPorts = [
        'http'  => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];
    private static $charUnreserved = 'a-zA-Z0-9_\-\.~';
    private static $charSubDelims = '!\$&\'\(\)\*\+,;=';
    private static $replaceQuery = ['=' => '%3D', '&' => '%26'];
    private $scheme = '';
    private $userInfo = '';
    private $host = '';
    private $port;
    private $path = '';
    private $query = '';
    private $fragment = '';
    public function __construct($uri = '')
    {
        if ($uri != '') {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new \InvalidArgumentException("Unable to parse URI: $uri");
            }
            $this->applyParts($parts);
        }
    }
    public function __toString()
    {
        return self::composeComponents(
            $this->scheme,
            $this->getAuthority(),
            $this->path,
            $this->query,
            $this->fragment
        );
    }
    public static function composeComponents($scheme, $authority, $path, $query, $fragment)
    {
        $uri = '';
        if ($scheme != '') {
            $uri .= $scheme . ':';
        }
        if ($authority != ''|| $scheme === 'file') {
            $uri .= '//' . $authority;
        }
        $uri .= $path;
        if ($query != '') {
            $uri .= '?' . $query;
        }
        if ($fragment != '') {
            $uri .= '#' . $fragment;
        }
        return $uri;
    }
    public static function isDefaultPort(UriInterface $uri)
    {
        return $uri->getPort() === null
            || (isset(self::$defaultPorts[$uri->getScheme()]) && $uri->getPort() === self::$defaultPorts[$uri->getScheme()]);
    }
    public static function isAbsolute(UriInterface $uri)
    {
        return $uri->getScheme() !== '';
    }
    public static function isNetworkPathReference(UriInterface $uri)
    {
        return $uri->getScheme() === '' && $uri->getAuthority() !== '';
    }
    public static function isAbsolutePathReference(UriInterface $uri)
    {
        return $uri->getScheme() === ''
            && $uri->getAuthority() === ''
            && isset($uri->getPath()[0])
            && $uri->getPath()[0] === '/';
    }
    public static function isRelativePathReference(UriInterface $uri)
    {
        return $uri->getScheme() === ''
            && $uri->getAuthority() === ''
            && (!isset($uri->getPath()[0]) || $uri->getPath()[0] !== '/');
    }
    public static function isSameDocumentReference(UriInterface $uri, UriInterface $base = null)
    {
        if ($base !== null) {
            $uri = UriResolver::resolve($base, $uri);
            return ($uri->getScheme() === $base->getScheme())
                && ($uri->getAuthority() === $base->getAuthority())
                && ($uri->getPath() === $base->getPath())
                && ($uri->getQuery() === $base->getQuery());
        }
        return $uri->getScheme() === '' && $uri->getAuthority() === '' && $uri->getPath() === '' && $uri->getQuery() === '';
    }
    public static function removeDotSegments($path)
    {
        return UriResolver::removeDotSegments($path);
    }
    public static function resolve(UriInterface $base, $rel)
    {
        if (!($rel instanceof UriInterface)) {
            $rel = new self($rel);
        }
        return UriResolver::resolve($base, $rel);
    }
    public static function withoutQueryValue(UriInterface $uri, $key)
    {
        $result = self::getFilteredQueryString($uri, [$key]);
        return $uri->withQuery(implode('&', $result));
    }
    public static function withQueryValue(UriInterface $uri, $key, $value)
    {
        $result = self::getFilteredQueryString($uri, [$key]);
        $result[] = self::generateQueryString($key, $value);
        return $uri->withQuery(implode('&', $result));
    }
    public static function withQueryValues(UriInterface $uri, array $keyValueArray)
    {
        $result = self::getFilteredQueryString($uri, array_keys($keyValueArray));
        foreach ($keyValueArray as $key => $value) {
            $result[] = self::generateQueryString($key, $value);
        }
        return $uri->withQuery(implode('&', $result));
    }
    public static function fromParts(array $parts)
    {
        $uri = new self();
        $uri->applyParts($parts);
        $uri->validateState();
        return $uri;
    }
    public function getScheme()
    {
        return $this->scheme;
    }
    public function getAuthority()
    {
        $authority = $this->host;
        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }
        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }
        return $authority;
    }
    public function getUserInfo()
    {
        return $this->userInfo;
    }
    public function getHost()
    {
        return $this->host;
    }
    public function getPort()
    {
        return $this->port;
    }
    public function getPath()
    {
        return $this->path;
    }
    public function getQuery()
    {
        return $this->query;
    }
    public function getFragment()
    {
        return $this->fragment;
    }
    public function withScheme($scheme)
    {
        $scheme = $this->filterScheme($scheme);
        if ($this->scheme === $scheme) {
            return $this;
        }
        $new = clone $this;
        $new->scheme = $scheme;
        $new->removeDefaultPort();
        $new->validateState();
        return $new;
    }
    public function withUserInfo($user, $password = null)
    {
        $info = $user;
        if ($password != '') {
            $info .= ':' . $password;
        }
        if ($this->userInfo === $info) {
            return $this;
        }
        $new = clone $this;
        $new->userInfo = $info;
        $new->validateState();
        return $new;
    }
    public function withHost($host)
    {
        $host = $this->filterHost($host);
        if ($this->host === $host) {
            return $this;
        }
        $new = clone $this;
        $new->host = $host;
        $new->validateState();
        return $new;
    }
    public function withPort($port)
    {
        $port = $this->filterPort($port);
        if ($this->port === $port) {
            return $this;
        }
        $new = clone $this;
        $new->port = $port;
        $new->removeDefaultPort();
        $new->validateState();
        return $new;
    }
    public function withPath($path)
    {
        $path = $this->filterPath($path);
        if ($this->path === $path) {
            return $this;
        }
        $new = clone $this;
        $new->path = $path;
        $new->validateState();
        return $new;
    }
    public function withQuery($query)
    {
        $query = $this->filterQueryAndFragment($query);
        if ($this->query === $query) {
            return $this;
        }
        $new = clone $this;
        $new->query = $query;
        return $new;
    }
    public function withFragment($fragment)
    {
        $fragment = $this->filterQueryAndFragment($fragment);
        if ($this->fragment === $fragment) {
            return $this;
        }
        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }
    private function applyParts(array $parts)
    {
        $this->scheme = isset($parts['scheme'])
            ? $this->filterScheme($parts['scheme'])
            : '';
        $this->userInfo = isset($parts['user']) ? $parts['user'] : '';
        $this->host = isset($parts['host'])
            ? $this->filterHost($parts['host'])
            : '';
        $this->port = isset($parts['port'])
            ? $this->filterPort($parts['port'])
            : null;
        $this->path = isset($parts['path'])
            ? $this->filterPath($parts['path'])
            : '';
        $this->query = isset($parts['query'])
            ? $this->filterQueryAndFragment($parts['query'])
            : '';
        $this->fragment = isset($parts['fragment'])
            ? $this->filterQueryAndFragment($parts['fragment'])
            : '';
        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }
        $this->removeDefaultPort();
    }
    private function filterScheme($scheme)
    {
        if (!is_string($scheme)) {
            throw new \InvalidArgumentException('Scheme must be a string');
        }
        return strtolower($scheme);
    }
    private function filterHost($host)
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException('Host must be a string');
        }
        return strtolower($host);
    }
    private function filterPort($port)
    {
        if ($port === null) {
            return null;
        }
        $port = (int) $port;
        if (1 > $port || 0xffff < $port) {
            throw new \InvalidArgumentException(
                sprintf('Invalid port: %d. Must be between 1 and 65535', $port)
            );
        }
        return $port;
    }
    private static function getFilteredQueryString(UriInterface $uri, array $keys)
    {
        $current = $uri->getQuery();
        if ($current === '') {
            return [];
        }
        $decodedKeys = array_map('rawurldecode', $keys);
        return array_filter(explode('&', $current), function ($part) use ($decodedKeys) {
            return !in_array(rawurldecode(explode('=', $part)[0]), $decodedKeys, true);
        });
    }
    private static function generateQueryString($key, $value)
    {
        $queryString = strtr($key, self::$replaceQuery);
        if ($value !== null) {
            $queryString .= '=' . strtr($value, self::$replaceQuery);
        }
        return $queryString;
    }
    private function removeDefaultPort()
    {
        if ($this->port !== null && self::isDefaultPort($this)) {
            $this->port = null;
        }
    }
    private function filterPath($path)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Path must be a string');
        }
        return preg_replace_callback(
            '/(?:[^' . self::$charUnreserved . self::$charSubDelims . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $path
        );
    }
    private function filterQueryAndFragment($str)
    {
        if (!is_string($str)) {
            throw new \InvalidArgumentException('Query and fragment must be a string');
        }
        return preg_replace_callback(
            '/(?:[^' . self::$charUnreserved . self::$charSubDelims . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $str
        );
    }
    private function rawurlencodeMatchZero(array $match)
    {
        return rawurlencode($match[0]);
    }
    private function validateState()
    {
        if ($this->host === '' && ($this->scheme === 'http' || $this->scheme === 'https')) {
            $this->host = self::HTTP_DEFAULT_HOST;
        }
        if ($this->getAuthority() === '') {
            if (0 === strpos($this->path, '//')) {
                throw new \InvalidArgumentException('The path of a URI without an authority must not start with two slashes "//"');
            }
            if ($this->scheme === '' && false !== strpos(explode('/', $this->path, 2)[0], ':')) {
                throw new \InvalidArgumentException('A relative URI must not have a path beginning with a segment containing a colon');
            }
        } elseif (isset($this->path[0]) && $this->path[0] !== '/') {
            @trigger_error(
                'The path of a URI with an authority must start with a slash "/" or be empty. Automagically fixing the URI ' .
                'by adding a leading slash to the path is deprecated since version 1.4 and will throw an exception instead.',
                E_USER_DEPRECATED
            );
            $this->path = '/' . $this->path;
        }
    }
}
