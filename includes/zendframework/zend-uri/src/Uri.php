<?php
namespace Zend\Uri;
use Zend\Escaper\Escaper;
use Zend\Validator;
class Uri implements UriInterface
{
    const CHAR_UNRESERVED   = 'a-zA-Z0-9_\-\.~';
    const CHAR_GEN_DELIMS   = ':\/\?#\[\]@';
    const CHAR_SUB_DELIMS   = '!\$&\'\(\)\*\+,;=';
    const CHAR_RESERVED     = ':\/\?#\[\]@!\$&\'\(\)\*\+,;=';
    const CHAR_QUERY_DELIMS = '!\$\'\(\)\*\,';
    const HOST_IPV4                           = 0x01;
    const HOST_IPV6                           = 0x02;
    const HOST_IPVFUTURE                      = 0x04;
    const HOST_IPVANY                         = 0x07;
    const HOST_DNS                            = 0x08;
    const HOST_DNS_OR_IPV4                    = 0x09;
    const HOST_DNS_OR_IPV6                    = 0x0A;
    const HOST_DNS_OR_IPV4_OR_IPV6            = 0x0B;
    const HOST_DNS_OR_IPVANY                  = 0x0F;
    const HOST_REGNAME                        = 0x10;
    const HOST_DNS_OR_IPV4_OR_IPV6_OR_REGNAME = 0x1B;
    const HOST_ALL                            = 0x1F;
    protected $scheme;
    protected $userInfo;
    protected $host;
    protected $port;
    protected $path;
    protected $query;
    protected $fragment;
    protected $validHostTypes = self::HOST_ALL;
    protected static $validSchemes = [];
    protected static $defaultPorts = [];
    protected static $escaper;
    public function __construct($uri = null)
    {
        if (is_string($uri)) {
            $this->parse($uri);
        } elseif ($uri instanceof UriInterface) {
            $this->setScheme($uri->getScheme());
            $this->setUserInfo($uri->getUserInfo());
            $this->setHost($uri->getHost());
            $this->setPort($uri->getPort());
            $this->setPath($uri->getPath());
            $this->setQuery($uri->getQuery());
            $this->setFragment($uri->getFragment());
        } elseif ($uri !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expecting a string or a URI object, received "%s"',
                (is_object($uri) ? get_class($uri) : gettype($uri))
            ));
        }
    }
    public static function setEscaper(Escaper $escaper)
    {
        static::$escaper = $escaper;
    }
    public static function getEscaper()
    {
        if (null === static::$escaper) {
            static::setEscaper(new Escaper());
        }
        return static::$escaper;
    }
    public function isValid()
    {
        if ($this->host) {
            if (strlen($this->path) > 0 && substr($this->path, 0, 1) != '/') {
                return false;
            }
            return true;
        }
        if ($this->userInfo || $this->port) {
            return false;
        }
        if ($this->path) {
            if (substr($this->path, 0, 2) == '//') {
                return false;
            }
            return true;
        }
        if (! ($this->query || $this->fragment)) {
            return false;
        }
        return true;
    }
    public function isValidRelative()
    {
        if ($this->scheme || $this->host || $this->userInfo || $this->port) {
            return false;
        }
        if ($this->path) {
            if (substr($this->path, 0, 2) == '//') {
                return false;
            }
            return true;
        }
        if (! ($this->query || $this->fragment)) {
            return false;
        }
        return true;
    }
    public function isAbsolute()
    {
        return $this->scheme !== null;
    }
    protected function reset()
    {
        $this->setScheme(null);
        $this->setPort(null);
        $this->setUserInfo(null);
        $this->setHost(null);
        $this->setPath(null);
        $this->setFragment(null);
        $this->setQuery(null);
    }
    public function parse($uri)
    {
        $this->reset();
        if (($scheme = self::parseScheme($uri)) !== null) {
            $this->setScheme($scheme);
            $uri = substr($uri, strlen($scheme) + 1) ?: '';
        }
        if (preg_match('|^//([^/\?#]*)|', $uri, $match)) {
            $authority = $match[1];
            $uri       = substr($uri, strlen($match[0]));
            if (strpos($authority, '@') !== false) {
                $segments  = explode('@', $authority);
                $authority = array_pop($segments);
                $userInfo  = implode('@', $segments);
                unset($segments);
                $this->setUserInfo($userInfo);
            }
            $nMatches = preg_match('/:[\d]{1,5}$/', $authority, $matches);
            if ($nMatches === 1) {
                $portLength = strlen($matches[0]);
                $port = substr($matches[0], 1);
                $this->setPort((int) $port);
                $authority = substr($authority, 0, -$portLength);
            }
            $this->setHost($authority);
        }
        if (! $uri) {
            return $this;
        }
        if (preg_match('|^[^\?#]*|', $uri, $match)) {
            $this->setPath($match[0]);
            $uri = substr($uri, strlen($match[0]));
        }
        if (! $uri) {
            return $this;
        }
        if (preg_match('|^\?([^#]*)|', $uri, $match)) {
            $this->setQuery($match[1]);
            $uri = substr($uri, strlen($match[0]));
        }
        if (! $uri) {
            return $this;
        }
        if ($uri && substr($uri, 0, 1) == '#') {
            $this->setFragment(substr($uri, 1));
        }
        return $this;
    }
    public function toString()
    {
        if (! $this->isValid()) {
            if ($this->isAbsolute() || ! $this->isValidRelative()) {
                throw new Exception\InvalidUriException(
                    'URI is not valid and cannot be converted into a string'
                );
            }
        }
        $uri = '';
        if ($this->scheme) {
            $uri .= $this->scheme . ':';
        }
        if ($this->host !== null) {
            $uri .= '//';
            if ($this->userInfo) {
                $uri .= $this->userInfo . '@';
            }
            $uri .= $this->host;
            if ($this->port) {
                $uri .= ':' . $this->port;
            }
        }
        if ($this->path) {
            $uri .= static::encodePath($this->path);
        } elseif ($this->host && ($this->query || $this->fragment)) {
            $uri .= '/';
        }
        if ($this->query) {
            $uri .= '?' . static::encodeQueryFragment($this->query);
        }
        if ($this->fragment) {
            $uri .= '#' . static::encodeQueryFragment($this->fragment);
        }
        return $uri;
    }
    public function normalize()
    {
        if ($this->scheme) {
            $this->scheme = static::normalizeScheme($this->scheme);
        }
        if ($this->host) {
            $this->host = static::normalizeHost($this->host);
        }
        if ($this->port) {
            $this->port = static::normalizePort($this->port, $this->scheme);
        }
        if ($this->path) {
            $this->path = static::normalizePath($this->path);
        }
        if ($this->query) {
            $this->query = static::normalizeQuery($this->query);
        }
        if ($this->fragment) {
            $this->fragment = static::normalizeFragment($this->fragment);
        }
        if ($this->host && empty($this->path)) {
            $this->path = '/';
        }
        return $this;
    }
    public function resolve($baseUri)
    {
        if ($this->isAbsolute()) {
            return $this;
        }
        if (is_string($baseUri)) {
            $baseUri = new static($baseUri);
        } elseif (! $baseUri instanceof Uri) {
            throw new Exception\InvalidArgumentException(
                'Provided base URI must be a string or a Uri object'
            );
        }
        if ($this->getHost()) {
            $this->setPath(static::removePathDotSegments($this->getPath()));
        } else {
            $basePath = $baseUri->getPath();
            $relPath  = $this->getPath();
            if (! $relPath) {
                $this->setPath($basePath);
                if (! $this->getQuery()) {
                    $this->setQuery($baseUri->getQuery());
                }
            } else {
                if (substr($relPath, 0, 1) == '/') {
                    $this->setPath(static::removePathDotSegments($relPath));
                } else {
                    if ($baseUri->getHost() && ! $basePath) {
                        $mergedPath = '/';
                    } else {
                        $mergedPath = substr($basePath, 0, strrpos($basePath, '/') + 1);
                    }
                    $this->setPath(static::removePathDotSegments($mergedPath . $relPath));
                }
            }
            $this->setUserInfo($baseUri->getUserInfo());
            $this->setHost($baseUri->getHost());
            $this->setPort($baseUri->getPort());
        }
        $this->setScheme($baseUri->getScheme());
        return $this;
    }
    public function makeRelative($baseUri)
    {
        $baseUri = new static($baseUri);
        $this->normalize();
        $baseUri->normalize();
        $host     = $this->getHost();
        $baseHost = $baseUri->getHost();
        if ($host && $baseHost && ($host != $baseHost)) {
            return $this;
        }
        $port     = $this->getPort();
        $basePort = $baseUri->getPort();
        if ($port && $basePort && ($port != $basePort)) {
            return $this;
        }
        $scheme     = $this->getScheme();
        $baseScheme = $baseUri->getScheme();
        if ($scheme && $baseScheme && ($scheme != $baseScheme)) {
            return $this;
        }
        $this->setHost(null)
             ->setPort(null)
             ->setScheme(null);
        if ($this->getPath() == $baseUri->getPath()) {
            $this->setPath('');
            return $this;
        }
        $pathParts = preg_split('|(/)|', $this->getPath(), null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $baseParts = preg_split('|(/)|', $baseUri->getPath(), null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $matchingParts = array_intersect_assoc($pathParts, $baseParts);
        foreach ($matchingParts as $index => $segment) {
            if ($index && ! isset($matchingParts[$index - 1])) {
                array_unshift($pathParts, '../');
                continue;
            }
            unset($pathParts[$index]);
        }
        $this->setPath(implode($pathParts));
        return $this;
    }
    public function getScheme()
    {
        return $this->scheme;
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
    public function getQueryAsArray()
    {
        $query = [];
        if ($this->query) {
            parse_str($this->query, $query);
        }
        return $query;
    }
    public function getFragment()
    {
        return $this->fragment;
    }
    public function setScheme($scheme)
    {
        if (($scheme !== null) && (! self::validateScheme($scheme))) {
            throw new Exception\InvalidUriPartException(sprintf(
                'Scheme "%s" is not valid or is not accepted by %s',
                $scheme,
                get_class($this)
            ), Exception\InvalidUriPartException::INVALID_SCHEME);
        }
        $this->scheme = $scheme;
        return $this;
    }
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
        return $this;
    }
    public function setHost($host)
    {
        if (($host !== '')
            && ($host !== null)
            && ! self::validateHost($host, $this->validHostTypes)
        ) {
            throw new Exception\InvalidUriPartException(sprintf(
                'Host "%s" is not valid or is not accepted by %s',
                $host,
                get_class($this)
            ), Exception\InvalidUriPartException::INVALID_HOSTNAME);
        }
        $this->host = $host;
        return $this;
    }
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }
    public function setQuery($query)
    {
        if (is_array($query)) {
            $query = str_replace('+', '%20', http_build_query($query));
        }
        $this->query = $query;
        return $this;
    }
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;
        return $this;
    }
    public function __toString()
    {
        try {
            return $this->toString();
        } catch (\Exception $e) {
            return '';
        }
    }
    public static function validateScheme($scheme)
    {
        if (! empty(static::$validSchemes)
            && ! in_array(strtolower($scheme), static::$validSchemes)
        ) {
            return false;
        }
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9\-\.+]*$/', $scheme);
    }
    public static function validateUserInfo($userInfo)
    {
        $regex = '/^(?:[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ':]+|%[A-Fa-f0-9]{2})*$/';
        return (bool) preg_match($regex, $userInfo);
    }
    public static function validateHost($host, $allowed = self::HOST_ALL)
    {
        if ($allowed & self::HOST_IPVANY) {
            if (static::isValidIpAddress($host, $allowed)) {
                return true;
            }
        }
        if ($allowed & self::HOST_REGNAME) {
            if (static::isValidRegName($host)) {
                return true;
            }
        }
        if ($allowed & self::HOST_DNS) {
            if (static::isValidDnsHostname($host)) {
                return true;
            }
        }
        return false;
    }
    public static function validatePort($port)
    {
        if ($port === 0) {
            return false;
        }
        if ($port) {
            $port = (int) $port;
            if ($port < 1 || $port > 0xffff) {
                return false;
            }
        }
        return true;
    }
    public static function validatePath($path)
    {
        $pchar   = '(?:[' . self::CHAR_UNRESERVED . ':@&=\+\$,]+|%[A-Fa-f0-9]{2})*';
        $segment = $pchar . "(?:;{$pchar})*";
        $regex   = "/^{$segment}(?:\/{$segment})*$/";
        return (bool) preg_match($regex, $path);
    }
    public static function validateQueryFragment($input)
    {
        $regex = '/^(?:[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ':@\/\?]+|%[A-Fa-f0-9]{2})*$/';
        return (bool) preg_match($regex, $input);
    }
    public static function encodeUserInfo($userInfo)
    {
        if (! is_string($userInfo)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expecting a string, got %s',
                (is_object($userInfo) ? get_class($userInfo) : gettype($userInfo))
            ));
        }
        $regex   = '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:]|%(?![A-Fa-f0-9]{2}))/';
        $escaper = static::getEscaper();
        $replace = function ($match) use ($escaper) {
            return $escaper->escapeUrl($match[0]);
        };
        return preg_replace_callback($regex, $replace, $userInfo);
    }
    public static function encodePath($path)
    {
        if (! is_string($path)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expecting a string, got %s',
                (is_object($path) ? get_class($path) : gettype($path))
            ));
        }
        $regex   = '/(?:[^' . self::CHAR_UNRESERVED . ')(:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/';
        $escaper = static::getEscaper();
        $replace = function ($match) use ($escaper) {
            return $escaper->escapeUrl($match[0]);
        };
        return preg_replace_callback($regex, $replace, $path);
    }
    public static function encodeQueryFragment($input)
    {
        if (! is_string($input)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expecting a string, got %s',
                (is_object($input) ? get_class($input) : gettype($input))
            ));
        }
        $regex   = '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/';
        $escaper = static::getEscaper();
        $replace = function ($match) use ($escaper) {
            return $escaper->escapeUrl($match[0]);
        };
        return preg_replace_callback($regex, $replace, $input);
    }
    public static function parseScheme($uriString)
    {
        if (! is_string($uriString)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expecting a string, got %s',
                (is_object($uriString) ? get_class($uriString) : gettype($uriString))
            ));
        }
        if (preg_match('/^([A-Za-z][A-Za-z0-9\.\+\-]*):/', $uriString, $match)) {
            return $match[1];
        }
        return;
    }
    public static function removePathDotSegments($path)
    {
        $output = '';
        while ($path) {
            if ($path == '..' || $path == '.') {
                break;
            }
            switch (true) {
                case $path == '/.':
                    $path = '/';
                    break;
                case $path == '/..':
                    $path   = '/';
                    $lastSlashPos = strrpos($output, '/', -1);
                    if (false === $lastSlashPos) {
                        break;
                    }
                    $output = substr($output, 0, $lastSlashPos);
                    break;
                case substr($path, 0, 4) == '/../':
                    $path   = '/' . substr($path, 4);
                    $lastSlashPos = strrpos($output, '/', -1);
                    if (false === $lastSlashPos) {
                        break;
                    }
                    $output = substr($output, 0, $lastSlashPos);
                    break;
                case substr($path, 0, 3) == '/./':
                    $path = substr($path, 2);
                    break;
                case substr($path, 0, 2) == './':
                    $path = substr($path, 2);
                    break;
                case substr($path, 0, 3) == '../':
                    $path = substr($path, 3);
                    break;
                default:
                    $slash = strpos($path, '/', 1);
                    if ($slash === false) {
                        $seg = $path;
                    } else {
                        $seg = substr($path, 0, $slash);
                    }
                    $output .= $seg;
                    $path    = substr($path, strlen($seg));
                    break;
            }
        }
        return $output;
    }
    public static function merge($baseUri, $relativeUri)
    {
        $uri = new static($relativeUri);
        return $uri->resolve($baseUri);
    }
    protected static function isValidIpAddress($host, $allowed)
    {
        $validatorParams = [
            'allowipv4'      => (bool) ($allowed & self::HOST_IPV4),
            'allowipv6'      => false,
            'allowipvfuture' => false,
            'allowliteral'   => false,
        ];
        $validator = new Validator\Ip($validatorParams);
        $return = $validator->isValid($host);
        if ($return) {
            return true;
        }
        $validatorParams = [
            'allowipv4'      => false,
            'allowipv6'      => (bool) ($allowed & self::HOST_IPV6),
            'allowipvfuture' => (bool) ($allowed & self::HOST_IPVFUTURE),
            'allowliteral'   => true,
        ];
        static $regex = '/^\[.*\]$/';
        $validator->setOptions($validatorParams);
        return preg_match($regex, $host) && $validator->isValid($host);
    }
    protected static function isValidDnsHostname($host)
    {
        $validator = new Validator\Hostname([
            'allow' => Validator\Hostname::ALLOW_DNS | Validator\Hostname::ALLOW_LOCAL,
        ]);
        return $validator->isValid($host);
    }
    protected static function isValidRegName($host)
    {
        $regex = '/^(?:[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ':@\/\?]+|%[A-Fa-f0-9]{2})+$/';
        return (bool) preg_match($regex, $host);
    }
    protected static function normalizeScheme($scheme)
    {
        return strtolower($scheme);
    }
    protected static function normalizeHost($host)
    {
        return strtolower($host);
    }
    protected static function normalizePort($port, $scheme = null)
    {
        if ($scheme
            && isset(static::$defaultPorts[$scheme])
            && ($port == static::$defaultPorts[$scheme])
        ) {
            return;
        }
        return $port;
    }
    protected static function normalizePath($path)
    {
        $path = self::encodePath(
            self::decodeUrlEncodedChars(
                self::removePathDotSegments($path),
                '/[' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]/'
            )
        );
        return $path;
    }
    protected static function normalizeQuery($query)
    {
        $query = self::encodeQueryFragment(
            self::decodeUrlEncodedChars(
                $query,
                '/[' . self::CHAR_UNRESERVED . self::CHAR_QUERY_DELIMS . ':@\/\?]/'
            )
        );
        return $query;
    }
    protected static function normalizeFragment($fragment)
    {
        $fragment = self::encodeQueryFragment(
            self::decodeUrlEncodedChars(
                $fragment,
                '/[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]/'
            )
        );
        return $fragment;
    }
    protected static function decodeUrlEncodedChars($input, $allowed = '')
    {
        $decodeCb = function ($match) use ($allowed) {
            $char = rawurldecode($match[0]);
            if (preg_match($allowed, $char)) {
                return $char;
            }
            return strtoupper($match[0]);
        };
        return preg_replace_callback('/%[A-Fa-f0-9]{2}/', $decodeCb, $input);
    }
}
