<?php
namespace DTS\eBaySDK;
class Sdk
{
    const VERSION = '14.0.0';
    public static $STRICT_PROPERTY_TYPES = true;
    private $config;
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    public function __call($name, array $args)
    {
        if (strpos($name, 'create') === 0) {
            return $this->createService(
                substr($name, 6),
                isset($args[0]) ? $args[0] : []
            );
        }
        throw new \BadMethodCallException("Unknown method: {$name}.");
    }
    public function createService($namespace, array $config = [])
    {
        $configuration = $this->config;
        if (isset($this->config[$namespace])) {
            $configuration = arrayMergeDeep($configuration, $this->config[$namespace]);
        }
        $configuration = arrayMergeDeep($configuration, $config);
        $service = "DTS\\eBaySDK\\{$namespace}\\Services\\{$namespace}Service";
        return new $service($configuration);
    }
}
