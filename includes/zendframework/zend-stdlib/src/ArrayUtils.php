<?php
namespace Zend\Stdlib;
use Traversable;
use Zend\Stdlib\ArrayUtils\MergeRemoveKey;
use Zend\Stdlib\ArrayUtils\MergeReplaceKeyInterface;
abstract class ArrayUtils
{
    const ARRAY_FILTER_USE_BOTH = 1;
    const ARRAY_FILTER_USE_KEY  = 2;
    public static function hasStringKeys($value, $allowEmpty = false)
    {
        if (! is_array($value)) {
            return false;
        }
        if (! $value) {
            return $allowEmpty;
        }
        return [] !== array_filter(array_keys($value), 'is_string');
    }
    public static function hasIntegerKeys($value, $allowEmpty = false)
    {
        if (! is_array($value)) {
            return false;
        }
        if (! $value) {
            return $allowEmpty;
        }
        return [] !== array_filter(array_keys($value), 'is_int');
    }
    public static function hasNumericKeys($value, $allowEmpty = false)
    {
        if (! is_array($value)) {
            return false;
        }
        if (! $value) {
            return $allowEmpty;
        }
        return [] !== array_filter(array_keys($value), 'is_numeric');
    }
    public static function isList($value, $allowEmpty = false)
    {
        if (! is_array($value)) {
            return false;
        }
        if (! $value) {
            return $allowEmpty;
        }
        return (array_values($value) === $value);
    }
    public static function isHashTable($value, $allowEmpty = false)
    {
        if (! is_array($value)) {
            return false;
        }
        if (! $value) {
            return $allowEmpty;
        }
        return (array_values($value) !== $value);
    }
    public static function inArray($needle, array $haystack, $strict = false)
    {
        if (! $strict) {
            if (is_int($needle) || is_float($needle)) {
                $needle = (string) $needle;
            }
            if (is_string($needle)) {
                foreach ($haystack as &$h) {
                    if (is_int($h) || is_float($h)) {
                        $h = (string) $h;
                    }
                }
            }
        }
        return in_array($needle, $haystack, $strict);
    }
    public static function iteratorToArray($iterator, $recursive = true)
    {
        if (! is_array($iterator) && ! $iterator instanceof Traversable) {
            throw new Exception\InvalidArgumentException(__METHOD__ . ' expects an array or Traversable object');
        }
        if (! $recursive) {
            if (is_array($iterator)) {
                return $iterator;
            }
            return iterator_to_array($iterator);
        }
        if (method_exists($iterator, 'toArray')) {
            return $iterator->toArray();
        }
        $array = [];
        foreach ($iterator as $key => $value) {
            if (is_scalar($value)) {
                $array[$key] = $value;
                continue;
            }
            if ($value instanceof Traversable) {
                $array[$key] = static::iteratorToArray($value, $recursive);
                continue;
            }
            if (is_array($value)) {
                $array[$key] = static::iteratorToArray($value, $recursive);
                continue;
            }
            $array[$key] = $value;
        }
        return $array;
    }
    public static function merge(array $a, array $b, $preserveNumericKeys = false)
    {
        foreach ($b as $key => $value) {
            if ($value instanceof MergeReplaceKeyInterface) {
                $a[$key] = $value->getData();
            } elseif (isset($a[$key]) || array_key_exists($key, $a)) {
                if ($value instanceof MergeRemoveKey) {
                    unset($a[$key]);
                } elseif (! $preserveNumericKeys && is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::merge($a[$key], $value, $preserveNumericKeys);
                } else {
                    $a[$key] = $value;
                }
            } else {
                if (! $value instanceof MergeRemoveKey) {
                    $a[$key] = $value;
                }
            }
        }
        return $a;
    }
    public static function filter(array $data, $callback, $flag = null)
    {
        if (! is_callable($callback)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Second parameter of %s must be callable',
                __METHOD__
            ));
        }
        return array_filter($data, $callback, $flag);
    }
}
