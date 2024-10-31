<?php
namespace DTS\eBaySDK;
class ConfigurationResolver
{
    private $definitions;
    private static $typeMap = [
        'array' => 'is_array',
        'bool' => 'is_bool',
        'callable' => 'is_callable',
        'int' => 'is_int',
        'string' => 'is_string'
    ];
    public function __construct(array $definitions)
    {
        $this->definitions = $definitions;
    }
    public function resolve(array $configuration)
    {
        foreach ($this->definitions as $key => $def) {
            if (!isset($configuration[$key])) {
                if (isset($def['default'])) {
                    $configuration[$key] = is_callable($def['default'])
                        ? $def['default']($configuration)
                        : $def['default'];
                } elseif (empty($def['required'])) {
                    continue;
                } else {
                    $this->throwRequired($configuration);
                }
            }
            $this->checkType($def['valid'], $key, $configuration[$key]);
            if (isset($def['fn'])) {
                $def['fn']($configuration[$key], $configuration);
            }
        }
        return $configuration;
    }
    public function resolveOptions(array $configuration)
    {
        foreach ($configuration as $key => $value) {
            if (isset($this->definitions[$key])) {
                $def = $this->definitions[$key];
                $this->checkType($def['valid'], $key, $value);
                if (isset($def['fn'])) {
                    $def['fn']($configuration[$key], $configuration);
                }
            }
        }
        return $configuration;
    }
    private function checkType(array $valid, $name, $provided)
    {
        foreach ($valid as $check) {
            if (isset(self::$typeMap[$check])) {
                $fn = self::$typeMap[$check];
                if ($fn($provided)) {
                    return;
                }
            } elseif ($provided instanceof $check) {
                return;
            }
        }
        $expected = implode('|', $valid);
        $msg = sprintf(
            'Invalid configuration value provided for "%s". Expected %s, but got %s',
            $name,
            $expected,
            describeType($provided)
        );
        throw new \InvalidArgumentException($msg);
    }
    private function throwRequired(array $configuration)
    {
        $missing = [];
        foreach ($this->definitions as $key => $def) {
            if (empty($def['required'])
                || isset($def['default'])
                || array_key_exists($key, $configuration)
            ) {
                continue;
            }
            $missing[] = $key;
        }
        $msg = "Missing required configuration options: \n\n";
        $msg .= implode("\n\n", $missing);
        throw new \InvalidArgumentException($msg);
    }
}
