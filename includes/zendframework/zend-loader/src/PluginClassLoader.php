<?php
namespace Zend\Loader;
use ArrayIterator;
use IteratorAggregate;
use Traversable;
class PluginClassLoader implements PluginClassLocator
{
    protected $plugins = [];
    protected static $staticMap = [];
    public function __construct($map = null)
    {
        if (! empty(static::$staticMap)) {
            $this->registerPlugins(static::$staticMap);
        }
        if ($map !== null) {
            $this->registerPlugins($map);
        }
    }
    public static function addStaticMap($map)
    {
        if (null === $map) {
            static::$staticMap = [];
            return;
        }
        if (! is_array($map) && ! $map instanceof Traversable) {
            throw new Exception\InvalidArgumentException('Expects an array or Traversable object');
        }
        foreach ($map as $key => $value) {
            static::$staticMap[$key] = $value;
        }
    }
    public function registerPlugin($shortName, $className)
    {
        $this->plugins[strtolower($shortName)] = $className;
        return $this;
    }
    public function registerPlugins($map)
    {
        if (is_string($map)) {
            if (! class_exists($map)) {
                throw new Exception\InvalidArgumentException('Map class provided is invalid');
            }
            $map = new $map();
        }
        if (is_array($map)) {
            $map = new ArrayIterator($map);
        }
        if (! $map instanceof Traversable) {
            throw new Exception\InvalidArgumentException('Map provided is invalid; must be traversable');
        }
        if ($map instanceof IteratorAggregate) {
            $map = $map->getIterator();
        }
        foreach ($map as $name => $class) {
            if (is_int($name) || is_numeric($name)) {
                if (! is_object($class) && class_exists($class)) {
                    $class = new $class();
                }
                if ($class instanceof Traversable) {
                    $this->registerPlugins($class);
                    continue;
                }
            }
            $this->registerPlugin($name, $class);
        }
        return $this;
    }
    public function unregisterPlugin($shortName)
    {
        $lookup = strtolower($shortName);
        if (array_key_exists($lookup, $this->plugins)) {
            unset($this->plugins[$lookup]);
        }
        return $this;
    }
    public function getRegisteredPlugins()
    {
        return $this->plugins;
    }
    public function isLoaded($name)
    {
        $lookup = strtolower($name);
        return isset($this->plugins[$lookup]);
    }
    public function getClassName($name)
    {
        return $this->load($name);
    }
    public function load($name)
    {
        if (! $this->isLoaded($name)) {
            return false;
        }
        return $this->plugins[strtolower($name)];
    }
    public function getIterator()
    {
        return new ArrayIterator($this->plugins);
    }
}
