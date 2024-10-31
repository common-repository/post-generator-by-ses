<?php
use GuzzleHttp\ClientInterface;
class Google_AuthHandler_AuthHandlerFactory
{
    public static function build($cache = null, array $cacheConfig = [])
    {
        $version = ClientInterface::VERSION;
        switch ($version[0]) {
      case '5':
        return new Google_AuthHandler_Guzzle5AuthHandler($cache, $cacheConfig);
      case '6':
        return new Google_AuthHandler_Guzzle6AuthHandler($cache, $cacheConfig);
      default:
        throw new Exception('Version not supported');
    }
    }
}
