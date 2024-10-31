<?php
namespace Zend\Json;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use ReflectionClass;
use Zend\Json\Exception\InvalidArgumentException;
use Zend\Json\Exception\RecursionException;
class Encoder
{
    protected $cycleCheck;
    protected $options = [];
    protected $visited = [];
    protected function __construct($cycleCheck = false, array $options = [])
    {
        $this->cycleCheck = $cycleCheck;
        $this->options = $options;
    }
    public static function encode($value, $cycleCheck = false, array $options = [])
    {
        $encoder = new static($cycleCheck, $options);
        if ($value instanceof JsonSerializable) {
            $value = $value->jsonSerialize();
        }
        return $encoder->encodeValue($value);
    }
    protected function encodeValue(&$value)
    {
        if (is_object($value)) {
            return $this->encodeObject($value);
        }
        if (is_array($value)) {
            return $this->encodeArray($value);
        }
        return $this->encodeDatum($value);
    }
    protected function encodeObject(&$value)
    {
        if ($this->cycleCheck) {
            if ($this->wasVisited($value)) {
                if (! isset($this->options['silenceCyclicalExceptions'])
                    || $this->options['silenceCyclicalExceptions'] !== true
                ) {
                    throw new RecursionException(sprintf(
                        'Cycles not supported in JSON encoding; cycle introduced by class "%s"',
                        get_class($value)
                    ));
                }
                return '"* RECURSION (' . str_replace('\\', '\\\\', get_class($value)) . ') *"';
            }
            $this->visited[] = $value;
        }
        $props = '';
        if (method_exists($value, 'toJson')) {
            $props = ',' . preg_replace("/^\{(.*)\}$/", '\\1', $value->toJson());
        } else {
            if ($value instanceof IteratorAggregate) {
                $propCollection = $value->getIterator();
            } elseif ($value instanceof Iterator) {
                $propCollection = $value;
            } else {
                $propCollection = get_object_vars($value);
            }
            foreach ($propCollection as $name => $propValue) {
                if (! isset($propValue)) {
                    continue;
                }
                $props .= ','
                    . $this->encodeValue($name)
                    . ':'
                    . $this->encodeValue($propValue);
            }
        }
        $className = get_class($value);
        return '{"__className":'
            . $this->encodeString($className)
            . $props . '}';
    }
    protected function wasVisited(&$value)
    {
        if (in_array($value, $this->visited, true)) {
            return true;
        }
        return false;
    }
    protected function encodeArray($array)
    {
        if (! empty($array) && (array_keys($array) !== range(0, count($array) - 1))) {
            return $this->encodeAssociativeArray($array);
        }
        $tmpArray = [];
        $result   = '[';
        $length   = count($array);
        for ($i = 0; $i < $length; $i++) {
            $tmpArray[] = $this->encodeValue($array[$i]);
        }
        $result .= implode(',', $tmpArray);
        $result .= ']';
        return $result;
    }
    protected function encodeAssociativeArray($array)
    {
        $tmpArray = [];
        $result   = '{';
        foreach ($array as $key => $value) {
            $tmpArray[] = sprintf(
                '%s:%s',
                $this->encodeString((string) $key),
                $this->encodeValue($value)
            );
        }
        $result .= implode(',', $tmpArray);
        $result .= '}';
        return $result;
    }
    protected function encodeDatum($value)
    {
        if (is_int($value) || is_float($value)) {
            return str_replace(',', '.', (string) $value);
        }
        if (is_string($value)) {
            return $this->encodeString($value);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return 'null';
    }
    protected function encodeString($string)
    {
        $search  = ['\\', "\n", "\t", "\r", "\b", "\f", '"', '\'', '&', '<', '>', '/'];
        $replace = ['\\\\', '\\n', '\\t', '\\r', '\\b', '\\f', '\\u0022', '\\u0027', '\\u0026', '\\u003C', '\\u003E', '\\/'];
        $string  = str_replace($search, $replace, $string);
        $string = str_replace([chr(0x08), chr(0x0C)], ['\b', '\f'], $string);
        $string = self::encodeUnicodeString($string);
        return '"' . $string . '"';
    }
    private static function encodeConstants(ReflectionClass $class)
    {
        $result    = 'constants:{';
        $constants = $class->getConstants();
        if (empty($constants)) {
            return $result . '}';
        }
        $tmpArray = [];
        foreach ($constants as $key => $value) {
            $tmpArray[] = sprintf('%s: %s', $key, self::encode($value));
        }
        $result .= implode(', ', $tmpArray);
        return $result . '}';
    }
    private static function encodeMethods(ReflectionClass $class)
    {
        $result  = 'methods:{';
        $started = false;
        foreach ($class->getMethods() as $method) {
            if (! $method->isPublic() || ! $method->isUserDefined()) {
                continue;
            }
            if ($started) {
                $result .= ',';
            }
            $started = true;
            $result .= sprintf('%s:function(', $method->getName());
            if ('__construct' === $method->getName()) {
                $result .= '){}';
                continue;
            }
            $argsStarted = false;
            $argNames    = 'var argNames=[';
            foreach ($method->getParameters() as $param) {
                if ($argsStarted) {
                    $result .= ',';
                }
                $result .= $param->getName();
                if ($argsStarted) {
                    $argNames .= ',';
                }
                $argNames .= sprintf('"%s"', $param->getName());
                $argsStarted = true;
            }
            $argNames .= '];';
            $result .= '){'
                . $argNames
                . 'var result = ZAjaxEngine.invokeRemoteMethod('
                . "this, '"
                . $method->getName()
                . "',argNames,arguments);"
                . 'return(result);}';
        }
        return $result . '}';
    }
    private static function encodeVariables(ReflectionClass $class)
    {
        $propValues = get_class_vars($class->getName());
        $result     = 'variables:{';
        $tmpArray   = [];
        foreach ($class->getProperties() as $prop) {
            if (! $prop->isPublic()) {
                continue;
            }
            $name = $prop->getName();
            $tmpArray[] = sprintf('%s:%s', $name, self::encode($propValues[$name]));
        }
        $result .= implode(',', $tmpArray);
        return $result . '}';
    }
    public static function encodeClass($className, $package = '')
    {
        $class = new ReflectionClass($className);
        if (! $class->isInstantiable()) {
            throw new InvalidArgumentException(sprintf(
                '"%s" must be instantiable',
                $className
            ));
        }
        return sprintf(
            'Class.create(\'%s%s\',{%s,%s,%s});',
            $package,
            $className,
            self::encodeConstants($class),
            self::encodeMethods($class),
            self::encodeVariables($class)
        );
    }
    public static function encodeClasses(array $classNames, $package = '')
    {
        $result = '';
        foreach ($classNames as $className) {
            $result .= static::encodeClass($className, $package);
        }
        return $result;
    }
    public static function encodeUnicodeString($value)
    {
        $strlenVar = strlen($value);
        $ascii     = '';
        for ($i = 0; $i < $strlenVar; $i++) {
            $ordVarC = ord($value[$i]);
            switch (true) {
                case ($ordVarC >= 0x20) && ($ordVarC <= 0x7F):
                                        $ascii .= $value[$i];
                    break;
                case ($ordVarC & 0xE0) == 0xC0:
                                                            $char = pack('C*', $ordVarC, ord($value[$i + 1]));
                    $i += 1;
                    $utf16 = self::utf82utf16($char);
                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                    break;
                case ($ordVarC & 0xF0) == 0xE0:
                                                            $char = pack(
                        'C*',
                        $ordVarC,
                        ord($value[$i + 1]),
                        ord($value[$i + 2])
                    );
                    $i += 2;
                    $utf16 = self::utf82utf16($char);
                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                    break;
                case ($ordVarC & 0xF8) == 0xF0:
                                                            $char = pack(
                        'C*',
                        $ordVarC,
                        ord($value[$i + 1]),
                        ord($value[$i + 2]),
                        ord($value[$i + 3])
                    );
                    $i += 3;
                    $utf16 = self::utf82utf16($char);
                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                    break;
                case ($ordVarC & 0xFC) == 0xF8:
                                                            $char = pack(
                        'C*',
                        $ordVarC,
                        ord($value[$i + 1]),
                        ord($value[$i + 2]),
                        ord($value[$i + 3]),
                        ord($value[$i + 4])
                    );
                    $i += 4;
                    $utf16 = self::utf82utf16($char);
                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                    break;
                case ($ordVarC & 0xFE) == 0xFC:
                                                            $char = pack(
                        'C*',
                        $ordVarC,
                        ord($value[$i + 1]),
                        ord($value[$i + 2]),
                        ord($value[$i + 3]),
                        ord($value[$i + 4]),
                        ord($value[$i + 5])
                    );
                    $i += 5;
                    $utf16 = self::utf82utf16($char);
                    $ascii .= sprintf('\u%04s', bin2hex($utf16));
                    break;
            }
        }
        return $ascii;
    }
    protected static function utf82utf16($utf8)
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }
        switch (strlen($utf8)) {
            case 1:
                                                return $utf8;
            case 2:
                                                return chr(0x07 & (ord($utf8{0}) >> 2)) . chr((0xC0 & (ord($utf8{0}) << 6)) | (0x3F & ord($utf8{1})));
            case 3:
                                                return chr((0xF0 & (ord($utf8{0}) << 4))
                    | (0x0F & (ord($utf8{1}) >> 2))) . chr((0xC0 & (ord($utf8{1}) << 6))
                    | (0x7F & ord($utf8{2})));
        }
        return '';
    }
}
