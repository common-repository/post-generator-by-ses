<?php
namespace DTS\eBaySDK;
use DTS\eBaySDK\Credentials\Credentials;
use DTS\eBaySDK\Credentials\CredentialsProvider;
use DTS\eBaySDK\Credentials\CredentialsInterface;
function describeType($value)
{
    switch (gettype($value)) {
        case 'object':
            return 'object('. get_class($value) . ')';
        case 'array':
            return 'array(' . count($value) . ')';
        default:
            ob_start();
            var_dump($value);
            return str_replace('double(', 'float(', rtrim(ob_get_clean()));
    }
}
function arrayMergeDeep()
{
    $args = func_get_args();
    return arrayMergeDeepArray($args);
}
function arrayMergeDeepArray(array $arrays)
{
    $result = [];
    foreach ($arrays as $array) {
        foreach ($array as $key => $value) {
                                                if (is_integer($key)) {
                $result[] = $value;
            } elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                                $result[$key] = arrayMergeDeepArray(array($result[$key], $value));
            } else {
                                $result[$key] = $value;
            }
        }
    }
    return $result;
}
function applyCredentials($value, array &$configuration)
{
    if (is_callable($value)) {
        $c = $value();
        if ($c instanceof \InvalidArgumentException) {
            throw $c;
        } else {
            $configuration['credentials'] = $c;
        }
    } elseif ($value instanceof CredentialsInterface) {
        return;
    } elseif (is_array($value)
        && isset($value['appId'])
        && isset($value['certId'])
        && isset($value['devId'])
    ) {
        $configuration['credentials'] = new Credentials(
            $value['appId'],
            $value['certId'],
            $value['devId']
        );
    } else {
        throw new \InvalidArgumentException(
            'Credentials must be an instance of '
            . 'DTS\eBaySDK\Credentials\CredentialsInterface, an associative '
            . 'array that contains "appId", "certId", "devId", '
            . 'or a credentials provider function.'
        );
    }
}
function applyProfile($value, array &$configuration)
{
    $configuration['credentials'] = CredentialsProvider::ini($configuration['profile']);
}
function applyDebug($value, array &$configuration)
{
    if ($value !== false) {
        $configuration['debug'] = new Debugger($value === true ? [] : $value);
    } else {
        $configuration['debug'] = false;
    }
}
function defaultHttpHandler(array &$configuration)
{
    return new HttpHandler();
}
function checkPropertyType($type)
{
    if (\DTS\eBaySDK\Sdk::$STRICT_PROPERTY_TYPES) {
        return true;
    }
    switch ($type) {
        case 'integer':
        case 'string':
        case 'double':
        case 'boolean':
        case 'DateTime':
            return false;
        default:
            return true;
    }
}
