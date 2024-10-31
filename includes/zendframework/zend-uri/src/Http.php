<?php
namespace Zend\Uri;
class Http extends Uri
{
    protected static $validSchemes = [
        'http',
        'https'
    ];
    protected static $defaultPorts = [
        'http'  => 80,
        'https' => 443,
    ];
    protected $validHostTypes = self::HOST_DNS_OR_IPV4_OR_IPV6_OR_REGNAME;
    protected $user;
    protected $password;
    public function getUser()
    {
        return $this->user;
    }
    public function getPassword()
    {
        return $this->password;
    }
    public function getUserInfo()
    {
        return $this->userInfo;
    }
    public function setUser($user)
    {
        $this->user = null === $user ? null : (string) $user;
        $this->buildUserInfo();
        return $this;
    }
    public function setPassword($password)
    {
        $this->password = null === $password ? null : (string) $password;
        $this->buildUserInfo();
        return $this;
    }
    public function setUserInfo($userInfo)
    {
        $this->userInfo = null === $userInfo ? null : (string) $userInfo;
        $this->parseUserInfo();
        return $this;
    }
    public static function validateHost($host, $allowed = self::HOST_DNS_OR_IPV4_OR_IPV6)
    {
        return parent::validateHost($host, $allowed);
    }
    protected function parseUserInfo()
    {
        if (null === $this->userInfo) {
            $this->setUser(null);
            $this->setPassword(null);
            return;
        }
        if (false === strpos($this->userInfo, ':')) {
            $this->setUser($this->userInfo);
            $this->setPassword(null);
            return;
        }
        list($this->user, $this->password) = explode(':', $this->userInfo, 2);
    }
    protected function buildUserInfo()
    {
        if (null !== $this->password) {
            $this->userInfo = $this->user . ':' . $this->password;
        } else {
            $this->userInfo = $this->user;
        }
    }
    public function getPort()
    {
        if (empty($this->port)) {
            if (array_key_exists($this->scheme, static::$defaultPorts)) {
                return static::$defaultPorts[$this->scheme];
            }
        }
        return $this->port;
    }
    public function parse($uri)
    {
        parent::parse($uri);
        if (empty($this->path)) {
            $this->path = '/';
        }
        return $this;
    }
}
