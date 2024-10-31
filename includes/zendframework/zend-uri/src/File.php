<?php
namespace Zend\Uri;
class File extends Uri
{
    protected static $validSchemes = ['file'];
    public function isValid()
    {
        if ($this->query) {
            return false;
        }
        return parent::isValid();
    }
    public function setUserInfo($userInfo)
    {
        return $this;
    }
    public function setFragment($fragment)
    {
        return $this;
    }
    public static function fromUnixPath($path)
    {
        $url = new static('file:');
        if (substr($path, 0, 1) == '/') {
            $url->setHost('');
        }
        $url->setPath($path);
        return $url;
    }
    public static function fromWindowsPath($path)
    {
        $url = new static('file:');
        $path = str_replace(['/', '\\'], ['%2F', '/'], $path);
        if (preg_match('|^([a-zA-Z]:)?/|', $path)) {
            $url->setHost('');
        }
        $url->setPath($path);
        return $url;
    }
}
