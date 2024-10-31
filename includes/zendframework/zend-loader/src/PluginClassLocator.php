<?php
namespace Zend\Loader;
use IteratorAggregate;
interface PluginClassLocator extends ShortNameLocator, IteratorAggregate
{
    public function registerPlugin($shortName, $className);
    public function unregisterPlugin($shortName);
    public function getRegisteredPlugins();
}
