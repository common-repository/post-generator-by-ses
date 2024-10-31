<?php
namespace Zend\Uri;
abstract class UriFactory
{
    protected static $schemeClasses = [
        'http'   => 'Zend\Uri\Http',
        'https'  => 'Zend\Uri\Http',
        'mailto' => 'Zend\Uri\Mailto',
        'file'   => 'Zend\Uri\File',
        'urn'    => 'Zend\Uri\Uri',
        'tag'    => 'Zend\Uri\Uri',
    ];
    public static function registerScheme($scheme, $class)
    {
        $scheme = strtolower($scheme);
        static::$schemeClasses[$scheme] = $class;
    }
    public static function unregisterScheme($scheme)
    {
        $scheme = strtolower($scheme);
        if (isset(static::$schemeClasses[$scheme])) {
            unset(static::$schemeClasses[$scheme]);
        }
    }
    public static function getRegisteredSchemeClass($scheme)
    {
        if (isset(static::$schemeClasses[$scheme])) {
            return static::$schemeClasses[$scheme];
        }
        return;
    }
    public static function factory($uriString, $defaultScheme = null)
    {
        if (! is_string($uriString)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expecting a string, received "%s"',
                (is_object($uriString) ? get_class($uriString) : gettype($uriString))
            ));
        }
        $uri    = new Uri($uriString);
        $scheme = strtolower($uri->getScheme());
        if (! $scheme && $defaultScheme) {
            $scheme = $defaultScheme;
        }
        if ($scheme && ! isset(static::$schemeClasses[$scheme])) {
            throw new Exception\InvalidArgumentException(sprintf(
                'no class registered for scheme "%s"',
                $scheme
            ));
        }
        if ($scheme && isset(static::$schemeClasses[$scheme])) {
            $class = static::$schemeClasses[$scheme];
            $uri = new $class($uri);
            if (! $uri instanceof UriInterface) {
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        'class "%s" registered for scheme "%s" does not implement Zend\Uri\UriInterface',
                        $class,
                        $scheme
                    )
                );
            }
        }
        return $uri;
    }
}
