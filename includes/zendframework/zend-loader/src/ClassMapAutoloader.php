<?php
namespace Zend\Loader;
use Traversable;
require_once __DIR__ . '/SplAutoloader.php';
class ClassMapAutoloader implements SplAutoloader
{
    protected $mapsLoaded = [];
    protected $map = [];
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->setOptions($options);
        }
    }
    public function setOptions($options)
    {
        $this->registerAutoloadMaps($options);
        return $this;
    }
    public function registerAutoloadMap($map)
    {
        if (is_string($map)) {
            $location = $map;
            if ($this === ($map = $this->loadMapFromFile($location))) {
                return $this;
            }
        }
        if (! is_array($map)) {
            require_once __DIR__ . '/Exception/InvalidArgumentException.php';
            throw new Exception\InvalidArgumentException(sprintf(
                'Map file provided does not return a map. Map file: "%s"',
                (isset($location) && is_string($location) ? $location : 'unexpected type: ' . gettype($map))
            ));
        }
        $this->map = $map + $this->map;
        if (isset($location)) {
            $this->mapsLoaded[] = $location;
        }
        return $this;
    }
    public function registerAutoloadMaps($locations)
    {
        if (! is_array($locations) && ! ($locations instanceof Traversable)) {
            require_once __DIR__ . '/Exception/InvalidArgumentException.php';
            throw new Exception\InvalidArgumentException('Map list must be an array or implement Traversable');
        }
        foreach ($locations as $location) {
            $this->registerAutoloadMap($location);
        }
        return $this;
    }
    public function getAutoloadMap()
    {
        return $this->map;
    }
    public function autoload($class)
    {
        if (isset($this->map[$class])) {
            require_once $this->map[$class];
            return $class;
        }
        return false;
    }
    public function register()
    {
        spl_autoload_register([$this, 'autoload'], true, true);
    }
    protected function loadMapFromFile($location)
    {
        if (! file_exists($location)) {
            require_once __DIR__ . '/Exception/InvalidArgumentException.php';
            throw new Exception\InvalidArgumentException(sprintf(
                'Map file provided does not exist. Map file: "%s"',
                (is_string($location) ? $location : 'unexpected type: ' . gettype($location))
            ));
        }
        if (! $path = static::realPharPath($location)) {
            $path = realpath($location);
        }
        if (in_array($path, $this->mapsLoaded)) {
            return $this;
        }
        $map = include $path;
        return $map;
    }
    public static function realPharPath($path)
    {
        if (! preg_match('|^phar:(/{2,3})|', $path, $match)) {
            return;
        }
        $prefixLength  = 5 + strlen($match[1]);
        $parts = explode('/', str_replace(['/', '\\'], '/', substr($path, $prefixLength)));
        $parts = array_values(array_filter($parts, function ($p) {
            return $p !== '' && $p !== '.';
        }));
        array_walk($parts, function ($value, $key) use (&$parts) {
            if ($value === '..') {
                unset($parts[$key], $parts[$key - 1]);
                $parts = array_values($parts);
            }
        });
        if (file_exists($realPath = str_pad('phar:', $prefixLength, '/') . implode('/', $parts))) {
            return $realPath;
        }
    }
}
