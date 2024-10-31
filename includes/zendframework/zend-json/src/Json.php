<?php
namespace Zend\Json;
use SplQueue;
use Zend\Json\Exception\RuntimeException;
class Json
{
    const TYPE_ARRAY  = 1;
    const TYPE_OBJECT = 0;
    public static $useBuiltinEncoderDecoder = false;
    public static function decode($encodedValue, $objectDecodeType = self::TYPE_OBJECT)
    {
        $encodedValue = (string) $encodedValue;
        if (function_exists('json_decode') && static::$useBuiltinEncoderDecoder !== true) {
            return self::decodeViaPhpBuiltIn($encodedValue, $objectDecodeType);
        }
        return Decoder::decode($encodedValue, $objectDecodeType);
    }
    public static function encode($valueToEncode, $cycleCheck = false, array $options = [])
    {
        if (is_object($valueToEncode)) {
            if (method_exists($valueToEncode, 'toJson')) {
                return $valueToEncode->toJson();
            }
            if (method_exists($valueToEncode, 'toArray')) {
                return static::encode($valueToEncode->toArray(), $cycleCheck, $options);
            }
        }
        $javascriptExpressions = new SplQueue();
        if (isset($options['enableJsonExprFinder'])
           && $options['enableJsonExprFinder'] == true
        ) {
            $valueToEncode = static::recursiveJsonExprFinder($valueToEncode, $javascriptExpressions);
        }
        $prettyPrint = (isset($options['prettyPrint']) && ($options['prettyPrint'] === true));
        $encodedResult = self::encodeValue($valueToEncode, $cycleCheck, $options, $prettyPrint);
        $encodedResult = self::injectJavascriptExpressions($encodedResult, $javascriptExpressions);
        return $encodedResult;
    }
    protected static function recursiveJsonExprFinder(
        $value,
        SplQueue $javascriptExpressions,
        $currentKey = null
    ) {
        if ($value instanceof Expr) {
            $magicKey = '____' . $currentKey . '_' . (count($javascriptExpressions));
            $javascriptExpressions->enqueue([
                                'magicKey' => (is_int($currentKey)) ? $magicKey : Encoder::encodeUnicodeString($magicKey),
                'value'    => $value,
            ]);
            return $magicKey;
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = static::recursiveJsonExprFinder($value[$k], $javascriptExpressions, $k);
            }
            return $value;
        }
        if (is_object($value)) {
            foreach ($value as $k => $v) {
                $value->$k = static::recursiveJsonExprFinder($value->$k, $javascriptExpressions, $k);
            }
            return $value;
        }
        return $value;
    }
    public static function prettyPrint($json, array $options = [])
    {
        $tokens       = preg_split('|([\{\}\]\[,])|', $json, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result       = '';
        $indentLevel  = 0;
        $indentString = isset($options['indent']) ? $options['indent'] : '    ';
        $inLiteral    = false;
        $openingBrackets = ['{', '['];
        $closingBrackets = ['}', ']'];
        $bracketPairs = array_combine(
            $openingBrackets,
            $closingBrackets
        );
        $count = count($tokens);
        for ($i = 0; $i < $count; ++$i) {
            $token = trim($tokens[$i]);
            if ($token === '') {
                continue;
            }
            if (preg_match('/^("(?:.*)"):[ ]?(.*)$/', $token, $matches)) {
                $token = $matches[1] . ': ' . $matches[2];
            }
            $prefix = str_repeat($indentString, $indentLevel);
            if (! $inLiteral && in_array($token, $openingBrackets, true)) {
                $indentLevel++;
                if ($result != '' && $result[strlen($result) - 1] === "\n") {
                    $result .= $prefix;
                }
                $result .= $token;
                $closingBracket = $bracketPairs[$token];
                do {
                    ++$i;
                } while ($i < $count && '' === trim($tokens[$i]));
                if ($closingBracket === $tokens[$i]) {
                    --$indentLevel;
                    $result .= $tokens[$i];
                    continue;
                }
                --$i;
                $result .= "\n";
                continue;
            }
            if (! $inLiteral && in_array($token, $closingBrackets, true)) {
                $indentLevel--;
                $prefix = str_repeat($indentString, $indentLevel);
                $result .= "\n" . $prefix . $token;
                continue;
            }
            if (! $inLiteral && $token === ',') {
                $result .= $token . "\n";
                continue;
            }
            $result .= ($inLiteral ? '' : $prefix) . $token;
            $token = str_replace('\\', '', $token);
            if ((substr_count($token, '"') - substr_count($token, '\\"')) % 2 !== 0) {
                $inLiteral = ! $inLiteral;
            }
        }
        return $result;
    }
    private static function decodeViaPhpBuiltIn($encodedValue, $objectDecodeType)
    {
        $decoded = json_decode($encodedValue, (bool) $objectDecodeType);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $decoded;
            case JSON_ERROR_DEPTH:
                throw new RuntimeException('Decoding failed: Maximum stack depth exceeded');
            case JSON_ERROR_CTRL_CHAR:
                throw new RuntimeException('Decoding failed: Unexpected control character found');
            case JSON_ERROR_SYNTAX:
                throw new RuntimeException('Decoding failed: Syntax error');
            default:
                throw new RuntimeException('Decoding failed');
        }
    }
    private static function encodeValue($valueToEncode, $cycleCheck, array $options, $prettyPrint)
    {
        if (function_exists('json_encode') && static::$useBuiltinEncoderDecoder !== true) {
            return self::encodeViaPhpBuiltIn($valueToEncode, $prettyPrint);
        }
        return self::encodeViaEncoder($valueToEncode, $cycleCheck, $options, $prettyPrint);
    }
    private static function encodeViaPhpBuiltIn($valueToEncode, $prettyPrint = false)
    {
        if (! function_exists('json_encode') || static::$useBuiltinEncoderDecoder === true) {
            return false;
        }
        $encodeOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
        if ($prettyPrint) {
            $encodeOptions |= JSON_PRETTY_PRINT;
        }
        return json_encode($valueToEncode, $encodeOptions);
    }
    private static function encodeViaEncoder($valueToEncode, $cycleCheck, array $options, $prettyPrint)
    {
        $encodedResult = Encoder::encode($valueToEncode, $cycleCheck, $options);
        if ($prettyPrint) {
            return self::prettyPrint($encodedResult, ['indent' => '    ']);
        }
        return $encodedResult;
    }
    private static function injectJavascriptExpressions($encodedValue, SplQueue $javascriptExpressions)
    {
        foreach ($javascriptExpressions as $expression) {
            $encodedValue = str_replace(
                sprintf('"%s"', $expression['magicKey']),
                $expression['value'],
                (string) $encodedValue
            );
        }
        return $encodedValue;
    }
}
