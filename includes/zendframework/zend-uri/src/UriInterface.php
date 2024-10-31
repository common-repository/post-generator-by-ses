<?php
namespace Zend\Uri;
interface UriInterface
{
    public function __construct($uri = null);
    public function isValid();
    public function isValidRelative();
    public function isAbsolute();
    public function parse($uri);
    public function toString();
    public function normalize();
    public function makeRelative($baseUri);
    public function getScheme();
    public function getUserInfo();
    public function getHost();
    public function getPort();
    public function getPath();
    public function getQuery();
    public function getQueryAsArray();
    public function getFragment();
    public function setScheme($scheme);
    public function setUserInfo($userInfo);
    public function setHost($host);
    public function setPort($port);
    public function setPath($path);
    public function setQuery($query);
    public function setFragment($fragment);
    public function __toString();
}
