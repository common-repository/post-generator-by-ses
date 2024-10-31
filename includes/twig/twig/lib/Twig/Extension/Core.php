<?php
if (!defined('ENT_SUBSTITUTE')) {
    define('ENT_SUBSTITUTE', 8);
}
class Twig_Extension_Core extends Twig_Extension
{
    protected $dateFormats = ['F j, Y H:i', '%d days'];
    protected $numberFormat = [0, '.', ','];
    protected $timezone = null;
    protected $escapers = [];
    public function setEscaper($strategy, $callable)
    {
        $this->escapers[$strategy] = $callable;
    }
    public function getEscapers()
    {
        return $this->escapers;
    }
    public function setDateFormat($format = null, $dateIntervalFormat = null)
    {
        if (null !== $format) {
            $this->dateFormats[0] = $format;
        }
        if (null !== $dateIntervalFormat) {
            $this->dateFormats[1] = $dateIntervalFormat;
        }
    }
    public function getDateFormat()
    {
        return $this->dateFormats;
    }
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);
    }
    public function getTimezone()
    {
        if (null === $this->timezone) {
            $this->timezone = new DateTimeZone(date_default_timezone_get());
        }
        return $this->timezone;
    }
    public function setNumberFormat($decimal, $decimalPoint, $thousandSep)
    {
        $this->numberFormat = [$decimal, $decimalPoint, $thousandSep];
    }
    public function getNumberFormat()
    {
        return $this->numberFormat;
    }
    public function getTokenParsers()
    {
        return [
            new Twig_TokenParser_For(),
            new Twig_TokenParser_If(),
            new Twig_TokenParser_Extends(),
            new Twig_TokenParser_Include(),
            new Twig_TokenParser_Block(),
            new Twig_TokenParser_Use(),
            new Twig_TokenParser_Filter(),
            new Twig_TokenParser_Macro(),
            new Twig_TokenParser_Import(),
            new Twig_TokenParser_From(),
            new Twig_TokenParser_Set(),
            new Twig_TokenParser_Spaceless(),
            new Twig_TokenParser_Flush(),
            new Twig_TokenParser_Do(),
            new Twig_TokenParser_Embed(),
            new Twig_TokenParser_With(),
            new Twig_TokenParser_Deprecated(),
        ];
    }
    public function getFilters()
    {
        $filters = [
                        new Twig_SimpleFilter('date', 'twig_date_format_filter', ['needs_environment' => true]),
            new Twig_SimpleFilter('date_modify', 'twig_date_modify_filter', ['needs_environment' => true]),
            new Twig_SimpleFilter('format', 'sprintf'),
            new Twig_SimpleFilter('replace', 'twig_replace_filter'),
            new Twig_SimpleFilter('number_format', 'twig_number_format_filter', ['needs_environment' => true]),
            new Twig_SimpleFilter('abs', 'abs'),
            new Twig_SimpleFilter('round', 'twig_round'),
                        new Twig_SimpleFilter('url_encode', 'twig_urlencode_filter'),
            new Twig_SimpleFilter('json_encode', 'twig_jsonencode_filter'),
            new Twig_SimpleFilter('convert_encoding', 'twig_convert_encoding'),
                        new Twig_SimpleFilter('title', 'twig_title_string_filter', ['needs_environment' => true]),
            new Twig_SimpleFilter('capitalize', 'twig_capitalize_string_filter', ['needs_environment' => true]),
            new Twig_SimpleFilter('upper', 'strtoupper'),
            new Twig_SimpleFilter('lower', 'strtolower'),
            new Twig_SimpleFilter('striptags', 'strip_tags'),
            new Twig_SimpleFilter('trim', 'twig_trim_filter'),
            new Twig_SimpleFilter('nl2br', 'nl2br', ['pre_escape' => 'html', 'is_safe' => ['html']]),
                        new Twig_SimpleFilter('join', 'twig_join_filter'),
            new Twig_SimpleFilter('split', 'twig_split_filter', ['needs_environment' => true]),
            new Twig_SimpleFilter('sort', 'twig_sort_filter'),
            new Twig_SimpleFilter('merge', 'twig_array_merge'),
            new Twig_SimpleFilter('batch', 'twig_array_batch'),
                        new Twig_SimpleFilter('reverse', 'twig_reverse_filter', ['needs_environment' => true]),
            new Twig_SimpleFilter('length', 'twig_length_filter', ['needs_environment' => true]),
            new Twig_SimpleFilter('slice', 'twig_slice', ['needs_environment' => true]),
            new Twig_SimpleFilter('first', 'twig_first', ['needs_environment' => true]),
            new Twig_SimpleFilter('last', 'twig_last', ['needs_environment' => true]),
                        new Twig_SimpleFilter('default', '_twig_default_filter', ['node_class' => 'Twig_Node_Expression_Filter_Default']),
            new Twig_SimpleFilter('keys', 'twig_get_array_keys_filter'),
                        new Twig_SimpleFilter('escape', 'twig_escape_filter', ['needs_environment' => true, 'is_safe_callback' => 'twig_escape_filter_is_safe']),
            new Twig_SimpleFilter('e', 'twig_escape_filter', ['needs_environment' => true, 'is_safe_callback' => 'twig_escape_filter_is_safe']),
        ];
        if (function_exists('mb_get_info')) {
            $filters[] = new Twig_SimpleFilter('upper', 'twig_upper_filter', ['needs_environment' => true]);
            $filters[] = new Twig_SimpleFilter('lower', 'twig_lower_filter', ['needs_environment' => true]);
        }
        return $filters;
    }
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('max', 'max'),
            new Twig_SimpleFunction('min', 'min'),
            new Twig_SimpleFunction('range', 'range'),
            new Twig_SimpleFunction('constant', 'twig_constant'),
            new Twig_SimpleFunction('cycle', 'twig_cycle'),
            new Twig_SimpleFunction('random', 'twig_random', ['needs_environment' => true]),
            new Twig_SimpleFunction('date', 'twig_date_converter', ['needs_environment' => true]),
            new Twig_SimpleFunction('include', 'twig_include', ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]),
            new Twig_SimpleFunction('source', 'twig_source', ['needs_environment' => true, 'is_safe' => ['all']]),
        ];
    }
    public function getTests()
    {
        return [
            new Twig_SimpleTest('even', null, ['node_class' => 'Twig_Node_Expression_Test_Even']),
            new Twig_SimpleTest('odd', null, ['node_class' => 'Twig_Node_Expression_Test_Odd']),
            new Twig_SimpleTest('defined', null, ['node_class' => 'Twig_Node_Expression_Test_Defined']),
            new Twig_SimpleTest('sameas', null, ['node_class' => 'Twig_Node_Expression_Test_Sameas', 'deprecated' => '1.21', 'alternative' => 'same as']),
            new Twig_SimpleTest('same as', null, ['node_class' => 'Twig_Node_Expression_Test_Sameas']),
            new Twig_SimpleTest('none', null, ['node_class' => 'Twig_Node_Expression_Test_Null']),
            new Twig_SimpleTest('null', null, ['node_class' => 'Twig_Node_Expression_Test_Null']),
            new Twig_SimpleTest('divisibleby', null, ['node_class' => 'Twig_Node_Expression_Test_Divisibleby', 'deprecated' => '1.21', 'alternative' => 'divisible by']),
            new Twig_SimpleTest('divisible by', null, ['node_class' => 'Twig_Node_Expression_Test_Divisibleby']),
            new Twig_SimpleTest('constant', null, ['node_class' => 'Twig_Node_Expression_Test_Constant']),
            new Twig_SimpleTest('empty', 'twig_test_empty'),
            new Twig_SimpleTest('iterable', 'twig_test_iterable'),
        ];
    }
    public function getOperators()
    {
        return [
            [
                'not' => ['precedence' => 50, 'class' => 'Twig_Node_Expression_Unary_Not'],
                '-' => ['precedence' => 500, 'class' => 'Twig_Node_Expression_Unary_Neg'],
                '+' => ['precedence' => 500, 'class' => 'Twig_Node_Expression_Unary_Pos'],
            ],
            [
                'or' => ['precedence' => 10, 'class' => 'Twig_Node_Expression_Binary_Or', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'and' => ['precedence' => 15, 'class' => 'Twig_Node_Expression_Binary_And', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'b-or' => ['precedence' => 16, 'class' => 'Twig_Node_Expression_Binary_BitwiseOr', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'b-xor' => ['precedence' => 17, 'class' => 'Twig_Node_Expression_Binary_BitwiseXor', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'b-and' => ['precedence' => 18, 'class' => 'Twig_Node_Expression_Binary_BitwiseAnd', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '==' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_Equal', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '!=' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_NotEqual', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '<' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_Less', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '>' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_Greater', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '>=' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_GreaterEqual', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '<=' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_LessEqual', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'not in' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_NotIn', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'in' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_In', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'matches' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_Matches', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'starts with' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_StartsWith', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'ends with' => ['precedence' => 20, 'class' => 'Twig_Node_Expression_Binary_EndsWith', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '..' => ['precedence' => 25, 'class' => 'Twig_Node_Expression_Binary_Range', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '+' => ['precedence' => 30, 'class' => 'Twig_Node_Expression_Binary_Add', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '-' => ['precedence' => 30, 'class' => 'Twig_Node_Expression_Binary_Sub', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '~' => ['precedence' => 40, 'class' => 'Twig_Node_Expression_Binary_Concat', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '*' => ['precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_Mul', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '/' => ['precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_Div', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '//' => ['precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_FloorDiv', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '%' => ['precedence' => 60, 'class' => 'Twig_Node_Expression_Binary_Mod', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'is' => ['precedence' => 100, 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                'is not' => ['precedence' => 100, 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT],
                '**' => ['precedence' => 200, 'class' => 'Twig_Node_Expression_Binary_Power', 'associativity' => Twig_ExpressionParser::OPERATOR_RIGHT],
                '??' => ['precedence' => 300, 'class' => 'Twig_Node_Expression_NullCoalesce', 'associativity' => Twig_ExpressionParser::OPERATOR_RIGHT],
            ],
        ];
    }
    public function getName()
    {
        return 'core';
    }
}
function twig_cycle($values, $position)
{
    if (!is_array($values) && !$values instanceof ArrayAccess) {
        return $values;
    }
    return $values[$position % count($values)];
}
function twig_random(Twig_Environment $env, $values = null)
{
    if (null === $values) {
        return mt_rand();
    }
    if (is_int($values) || is_float($values)) {
        return $values < 0 ? mt_rand($values, 0) : mt_rand(0, $values);
    }
    if ($values instanceof Traversable) {
        $values = iterator_to_array($values);
    } elseif (is_string($values)) {
        if ('' === $values) {
            return '';
        }
        if (null !== $charset = $env->getCharset()) {
            if ('UTF-8' !== $charset) {
                $values = twig_convert_encoding($values, 'UTF-8', $charset);
            }
            $values = preg_split('/(?<!^)(?!$)/u', $values);
            if ('UTF-8' !== $charset) {
                foreach ($values as $i => $value) {
                    $values[$i] = twig_convert_encoding($value, $charset, 'UTF-8');
                }
            }
        } else {
            return $values[mt_rand(0, strlen($values) - 1)];
        }
    }
    if (!is_array($values)) {
        return $values;
    }
    if (0 === count($values)) {
        throw new Twig_Error_Runtime('The random function cannot pick from an empty array.');
    }
    return $values[array_rand($values, 1)];
}
function twig_date_format_filter(Twig_Environment $env, $date, $format = null, $timezone = null)
{
    if (null === $format) {
        $formats = $env->getExtension('Twig_Extension_Core')->getDateFormat();
        $format = $date instanceof DateInterval ? $formats[1] : $formats[0];
    }
    if ($date instanceof DateInterval) {
        return $date->format($format);
    }
    return twig_date_converter($env, $date, $timezone)->format($format);
}
function twig_date_modify_filter(Twig_Environment $env, $date, $modifier)
{
    $date = twig_date_converter($env, $date, false);
    $resultDate = $date->modify($modifier);
    return null === $resultDate ? $date : $resultDate;
}
function twig_date_converter(Twig_Environment $env, $date = null, $timezone = null)
{
    if (false !== $timezone) {
        if (null === $timezone) {
            $timezone = $env->getExtension('Twig_Extension_Core')->getTimezone();
        } elseif (!$timezone instanceof DateTimeZone) {
            $timezone = new DateTimeZone($timezone);
        }
    }
    if ($date instanceof DateTimeImmutable) {
        return false !== $timezone ? $date->setTimezone($timezone) : $date;
    }
    if ($date instanceof DateTime || $date instanceof DateTimeInterface) {
        $date = clone $date;
        if (false !== $timezone) {
            $date->setTimezone($timezone);
        }
        return $date;
    }
    if (null === $date || 'now' === $date) {
        return new DateTime($date, false !== $timezone ? $timezone : $env->getExtension('Twig_Extension_Core')->getTimezone());
    }
    $asString = (string) $date;
    if (ctype_digit($asString) || (!empty($asString) && '-' === $asString[0] && ctype_digit(substr($asString, 1)))) {
        $date = new DateTime('@' . $date);
    } else {
        $date = new DateTime($date, $env->getExtension('Twig_Extension_Core')->getTimezone());
    }
    if (false !== $timezone) {
        $date->setTimezone($timezone);
    }
    return $date;
}
function twig_replace_filter($str, $from, $to = null)
{
    if ($from instanceof Traversable) {
        $from = iterator_to_array($from);
    } elseif (is_string($from) && is_string($to)) {
        @trigger_error('Using "replace" with character by character replacement is deprecated since version 1.22 and will be removed in Twig 2.0', E_USER_DEPRECATED);
        return strtr($str, $from, $to);
    } elseif (!is_array($from)) {
        throw new Twig_Error_Runtime(sprintf('The "replace" filter expects an array or "Traversable" as replace values, got "%s".', is_object($from) ? get_class($from) : gettype($from)));
    }
    return strtr($str, $from);
}
function twig_round($value, $precision = 0, $method = 'common')
{
    if ('common' == $method) {
        return round($value, $precision);
    }
    if ('ceil' != $method && 'floor' != $method) {
        throw new Twig_Error_Runtime('The round filter only supports the "common", "ceil", and "floor" methods.');
    }
    return $method($value * pow(10, $precision)) / pow(10, $precision);
}
function twig_number_format_filter(Twig_Environment $env, $number, $decimal = null, $decimalPoint = null, $thousandSep = null)
{
    $defaults = $env->getExtension('Twig_Extension_Core')->getNumberFormat();
    if (null === $decimal) {
        $decimal = $defaults[0];
    }
    if (null === $decimalPoint) {
        $decimalPoint = $defaults[1];
    }
    if (null === $thousandSep) {
        $thousandSep = $defaults[2];
    }
    return number_format((float) $number, $decimal, $decimalPoint, $thousandSep);
}
function twig_urlencode_filter($url)
{
    if (is_array($url)) {
        if (defined('PHP_QUERY_RFC3986')) {
            return http_build_query($url, '', '&', PHP_QUERY_RFC3986);
        }
        return http_build_query($url, '', '&');
    }
    return rawurlencode($url);
}
if (PHP_VERSION_ID < 50300) {
    function twig_jsonencode_filter($value, $options = 0)
    {
        if ($value instanceof Twig_Markup) {
            $value = (string) $value;
        } elseif (is_array($value)) {
            array_walk_recursive($value, '_twig_markup2string');
        }
        return json_encode($value);
    }
} else {
    function twig_jsonencode_filter($value, $options = 0)
    {
        if ($value instanceof Twig_Markup) {
            $value = (string) $value;
        } elseif (is_array($value)) {
            array_walk_recursive($value, '_twig_markup2string');
        }
        return json_encode($value, $options);
    }
}
function _twig_markup2string(&$value)
{
    if ($value instanceof Twig_Markup) {
        $value = (string) $value;
    }
}
function twig_array_merge($arr1, $arr2)
{
    if ($arr1 instanceof Traversable) {
        $arr1 = iterator_to_array($arr1);
    } elseif (!is_array($arr1)) {
        throw new Twig_Error_Runtime(sprintf('The merge filter only works with arrays or "Traversable", got "%s" as first argument.', gettype($arr1)));
    }
    if ($arr2 instanceof Traversable) {
        $arr2 = iterator_to_array($arr2);
    } elseif (!is_array($arr2)) {
        throw new Twig_Error_Runtime(sprintf('The merge filter only works with arrays or "Traversable", got "%s" as second argument.', gettype($arr2)));
    }
    return array_merge($arr1, $arr2);
}
function twig_slice(Twig_Environment $env, $item, $start, $length = null, $preserveKeys = false)
{
    if ($item instanceof Traversable) {
        while ($item instanceof IteratorAggregate) {
            $item = $item->getIterator();
        }
        if ($start >= 0 && $length >= 0 && $item instanceof Iterator) {
            try {
                return iterator_to_array(new LimitIterator($item, $start, null === $length ? -1 : $length), $preserveKeys);
            } catch (OutOfBoundsException $exception) {
                return [];
            }
        }
        $item = iterator_to_array($item, $preserveKeys);
    }
    if (is_array($item)) {
        return array_slice($item, $start, $length, $preserveKeys);
    }
    $item = (string) $item;
    if (function_exists('mb_get_info') && null !== $charset = $env->getCharset()) {
        return (string) mb_substr($item, $start, null === $length ? mb_strlen($item, $charset) - $start : $length, $charset);
    }
    return (string) (null === $length ? substr($item, $start) : substr($item, $start, $length));
}
function twig_first(Twig_Environment $env, $item)
{
    $elements = twig_slice($env, $item, 0, 1, false);
    return is_string($elements) ? $elements : current($elements);
}
function twig_last(Twig_Environment $env, $item)
{
    $elements = twig_slice($env, $item, -1, 1, false);
    return is_string($elements) ? $elements : current($elements);
}
function twig_join_filter($value, $glue = '')
{
    if ($value instanceof Traversable) {
        $value = iterator_to_array($value, false);
    }
    return implode($glue, (array) $value);
}
function twig_split_filter(Twig_Environment $env, $value, $delimiter, $limit = null)
{
    if (!empty($delimiter)) {
        return null === $limit ? explode($delimiter, $value) : explode($delimiter, $value, $limit);
    }
    if (!function_exists('mb_get_info') || null === $charset = $env->getCharset()) {
        return str_split($value, null === $limit ? 1 : $limit);
    }
    if ($limit <= 1) {
        return preg_split('/(?<!^)(?!$)/u', $value);
    }
    $length = mb_strlen($value, $charset);
    if ($length < $limit) {
        return [$value];
    }
    $r = [];
    for ($i = 0; $i < $length; $i += $limit) {
        $r[] = mb_substr($value, $i, $limit, $charset);
    }
    return $r;
}
function _twig_default_filter($value, $default = '')
{
    if (twig_test_empty($value)) {
        return $default;
    }
    return $value;
}
function twig_get_array_keys_filter($array)
{
    if ($array instanceof Traversable) {
        while ($array instanceof IteratorAggregate) {
            $array = $array->getIterator();
        }
        if ($array instanceof Iterator) {
            $keys = [];
            $array->rewind();
            while ($array->valid()) {
                $keys[] = $array->key();
                $array->next();
            }
            return $keys;
        }
        $keys = [];
        foreach ($array as $key => $item) {
            $keys[] = $key;
        }
        return $keys;
    }
    if (!is_array($array)) {
        return [];
    }
    return array_keys($array);
}
function twig_reverse_filter(Twig_Environment $env, $item, $preserveKeys = false)
{
    if ($item instanceof Traversable) {
        return array_reverse(iterator_to_array($item), $preserveKeys);
    }
    if (is_array($item)) {
        return array_reverse($item, $preserveKeys);
    }
    if (null !== $charset = $env->getCharset()) {
        $string = (string) $item;
        if ('UTF-8' !== $charset) {
            $item = twig_convert_encoding($string, 'UTF-8', $charset);
        }
        preg_match_all('/./us', $item, $matches);
        $string = implode('', array_reverse($matches[0]));
        if ('UTF-8' !== $charset) {
            $string = twig_convert_encoding($string, $charset, 'UTF-8');
        }
        return $string;
    }
    return strrev((string) $item);
}
function twig_sort_filter($array)
{
    if ($array instanceof Traversable) {
        $array = iterator_to_array($array);
    } elseif (!is_array($array)) {
        throw new Twig_Error_Runtime(sprintf('The sort filter only works with arrays or "Traversable", got "%s".', gettype($array)));
    }
    asort($array);
    return $array;
}
function twig_in_filter($value, $compare)
{
    if (is_array($compare)) {
        return in_array($value, $compare, is_object($value) || is_resource($value));
    } elseif (is_string($compare) && (is_string($value) || is_int($value) || is_float($value))) {
        return '' === $value || false !== strpos($compare, (string) $value);
    } elseif ($compare instanceof Traversable) {
        if (is_object($value) || is_resource($value)) {
            foreach ($compare as $item) {
                if ($item === $value) {
                    return true;
                }
            }
        } else {
            foreach ($compare as $item) {
                if ($item == $value) {
                    return true;
                }
            }
        }
        return false;
    }
    return false;
}
function twig_trim_filter($string, $characterMask = null, $side = 'both')
{
    if (null === $characterMask) {
        $characterMask = " \t\n\r\0\x0B";
    }
    switch ($side) {
        case 'both':
            return trim($string, $characterMask);
        case 'left':
            return ltrim($string, $characterMask);
        case 'right':
            return rtrim($string, $characterMask);
        default:
            throw new Twig_Error_Runtime('Trimming side must be "left", "right" or "both".');
    }
}
function twig_escape_filter(Twig_Environment $env, $string, $strategy = 'html', $charset = null, $autoescape = false)
{
    if ($autoescape && $string instanceof Twig_Markup) {
        return $string;
    }
    if (!is_string($string)) {
        if (is_object($string) && method_exists($string, '__toString')) {
            $string = (string) $string;
        } elseif (in_array($strategy, ['html', 'js', 'css', 'html_attr', 'url'])) {
            return $string;
        }
    }
    if ('' === $string) {
        return '';
    }
    if (null === $charset) {
        $charset = $env->getCharset();
    }
    switch ($strategy) {
        case 'html':
                                                static $htmlspecialcharsCharsets = [
                'ISO-8859-1' => true, 'ISO8859-1' => true,
                'ISO-8859-15' => true, 'ISO8859-15' => true,
                'utf-8' => true, 'UTF-8' => true,
                'CP866' => true, 'IBM866' => true, '866' => true,
                'CP1251' => true, 'WINDOWS-1251' => true, 'WIN-1251' => true,
                '1251' => true,
                'CP1252' => true, 'WINDOWS-1252' => true, '1252' => true,
                'KOI8-R' => true, 'KOI8-RU' => true, 'KOI8R' => true,
                'BIG5' => true, '950' => true,
                'GB2312' => true, '936' => true,
                'BIG5-HKSCS' => true,
                'SHIFT_JIS' => true, 'SJIS' => true, '932' => true,
                'EUC-JP' => true, 'EUCJP' => true,
                'ISO8859-5' => true, 'ISO-8859-5' => true, 'MACROMAN' => true,
            ];
            if (isset($htmlspecialcharsCharsets[$charset])) {
                return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
            }
            if (isset($htmlspecialcharsCharsets[strtoupper($charset)])) {
                $htmlspecialcharsCharsets[$charset] = true;
                return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
            }
            $string = twig_convert_encoding($string, 'UTF-8', $charset);
            $string = htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return twig_convert_encoding($string, $charset, 'UTF-8');
        case 'js':
                                    if ('UTF-8' !== $charset) {
                                        $string = twig_convert_encoding($string, 'UTF-8', $charset);
                                    }
            if (!preg_match('//u', $string)) {
                throw new Twig_Error_Runtime('The string to escape is not a valid UTF-8 string.');
            }
            $string = preg_replace_callback('#[^a-zA-Z0-9,\._]#Su', '_twig_escape_js_callback', $string);
            if ('UTF-8' !== $charset) {
                $string = twig_convert_encoding($string, $charset, 'UTF-8');
            }
            return $string;
        case 'css':
            if ('UTF-8' !== $charset) {
                $string = twig_convert_encoding($string, 'UTF-8', $charset);
            }
            if (!preg_match('//u', $string)) {
                throw new Twig_Error_Runtime('The string to escape is not a valid UTF-8 string.');
            }
            $string = preg_replace_callback('#[^a-zA-Z0-9]#Su', '_twig_escape_css_callback', $string);
            if ('UTF-8' !== $charset) {
                $string = twig_convert_encoding($string, $charset, 'UTF-8');
            }
            return $string;
        case 'html_attr':
            if ('UTF-8' !== $charset) {
                $string = twig_convert_encoding($string, 'UTF-8', $charset);
            }
            if (!preg_match('//u', $string)) {
                throw new Twig_Error_Runtime('The string to escape is not a valid UTF-8 string.');
            }
            $string = preg_replace_callback('#[^a-zA-Z0-9,\.\-_]#Su', '_twig_escape_html_attr_callback', $string);
            if ('UTF-8' !== $charset) {
                $string = twig_convert_encoding($string, $charset, 'UTF-8');
            }
            return $string;
        case 'url':
            if (PHP_VERSION_ID < 50300) {
                return str_replace('%7E', '~', rawurlencode($string));
            }
            return rawurlencode($string);
        default:
            static $escapers;
            if (null === $escapers) {
                $escapers = $env->getExtension('Twig_Extension_Core')->getEscapers();
            }
            if (isset($escapers[$strategy])) {
                return call_user_func($escapers[$strategy], $env, $string, $charset);
            }
            $validStrategies = implode(', ', array_merge(['html', 'js', 'url', 'css', 'html_attr'], array_keys($escapers)));
            throw new Twig_Error_Runtime(sprintf('Invalid escaping strategy "%s" (valid ones: %s).', $strategy, $validStrategies));
    }
}
function twig_escape_filter_is_safe(Twig_Node $filterArgs)
{
    foreach ($filterArgs as $arg) {
        if ($arg instanceof Twig_Node_Expression_Constant) {
            return [$arg->getAttribute('value')];
        }
        return [];
    }
    return ['html'];
}
if (function_exists('mb_convert_encoding')) {
    function twig_convert_encoding($string, $to, $from)
    {
        return mb_convert_encoding($string, $to, $from);
    }
} elseif (function_exists('iconv')) {
    function twig_convert_encoding($string, $to, $from)
    {
        return iconv($from, $to, $string);
    }
} else {
    function twig_convert_encoding($string, $to, $from)
    {
        throw new Twig_Error_Runtime('No suitable convert encoding function (use UTF-8 as your encoding or install the iconv or mbstring extension).');
    }
}
if (function_exists('mb_ord')) {
    function twig_ord($string)
    {
        return mb_ord($string, 'UTF-8');
    }
} else {
    function twig_ord($string)
    {
        $code = ($string = unpack('C*', substr($string, 0, 4))) ? $string[1] : 0;
        if (0xF0 <= $code) {
            return (($code - 0xF0) << 18) + (($string[2] - 0x80) << 12) + (($string[3] - 0x80) << 6) + $string[4] - 0x80;
        }
        if (0xE0 <= $code) {
            return (($code - 0xE0) << 12) + (($string[2] - 0x80) << 6) + $string[3] - 0x80;
        }
        if (0xC0 <= $code) {
            return (($code - 0xC0) << 6) + $string[2] - 0x80;
        }
        return $code;
    }
}
function _twig_escape_js_callback($matches)
{
    $char = $matches[0];
    static $shortMap = [
        '\\' => '\\\\',
        '/' => '\\/',
        "\x08" => '\b',
        "\x0C" => '\f',
        "\x0A" => '\n',
        "\x0D" => '\r',
        "\x09" => '\t',
    ];
    if (isset($shortMap[$char])) {
        return $shortMap[$char];
    }
    $char = twig_convert_encoding($char, 'UTF-16BE', 'UTF-8');
    $char = strtoupper(bin2hex($char));
    if (4 >= strlen($char)) {
        return sprintf('\u%04s', $char);
    }
    return sprintf('\u%04s\u%04s', substr($char, 0, -4), substr($char, -4));
}
function _twig_escape_css_callback($matches)
{
    $char = $matches[0];
    return sprintf('\\%X ', 1 === strlen($char) ? ord($char) : twig_ord($char));
}
function _twig_escape_html_attr_callback($matches)
{
    $chr = $matches[0];
    $ord = ord($chr);
    if (($ord <= 0x1f && "\t" != $chr && "\n" != $chr && "\r" != $chr) || ($ord >= 0x7f && $ord <= 0x9f)) {
        return '&#xFFFD;';
    }
    if (1 == strlen($chr)) {
        static $entityMap = [
            34 => '&quot;',
            38 => '&amp;',
            60 => '&lt;',
            62 => '&gt;',
        ];
        if (isset($entityMap[$ord])) {
            return $entityMap[$ord];
        }
        return sprintf('&#x%02X;', $ord);
    }
    return sprintf('&#x%04X;', twig_ord($chr));
}
if (function_exists('mb_get_info')) {
    function twig_length_filter(Twig_Environment $env, $thing)
    {
        if (null === $thing) {
            return 0;
        }
        if (is_scalar($thing)) {
            return mb_strlen($thing, $env->getCharset());
        }
        if ($thing instanceof \SimpleXMLElement) {
            return count($thing);
        }
        if (is_object($thing) && method_exists($thing, '__toString') && !$thing instanceof \Countable) {
            return mb_strlen((string) $thing, $env->getCharset());
        }
        if ($thing instanceof \Countable || is_array($thing)) {
            return count($thing);
        }
        if ($thing instanceof \IteratorAggregate) {
            return iterator_count($thing);
        }
        return 1;
    }
    function twig_upper_filter(Twig_Environment $env, $string)
    {
        if (null !== $charset = $env->getCharset()) {
            return mb_strtoupper($string, $charset);
        }
        return strtoupper($string);
    }
    function twig_lower_filter(Twig_Environment $env, $string)
    {
        if (null !== $charset = $env->getCharset()) {
            return mb_strtolower($string, $charset);
        }
        return strtolower($string);
    }
    function twig_title_string_filter(Twig_Environment $env, $string)
    {
        if (null !== $charset = $env->getCharset()) {
            return mb_convert_case($string, MB_CASE_TITLE, $charset);
        }
        return ucwords(strtolower($string));
    }
    function twig_capitalize_string_filter(Twig_Environment $env, $string)
    {
        if (null !== $charset = $env->getCharset()) {
            return mb_strtoupper(mb_substr($string, 0, 1, $charset), $charset) . mb_strtolower(mb_substr($string, 1, mb_strlen($string, $charset), $charset), $charset);
        }
        return ucfirst(strtolower($string));
    }
} else {
    function twig_length_filter(Twig_Environment $env, $thing)
    {
        if (null === $thing) {
            return 0;
        }
        if (is_scalar($thing)) {
            return strlen($thing);
        }
        if ($thing instanceof \SimpleXMLElement) {
            return count($thing);
        }
        if (is_object($thing) && method_exists($thing, '__toString') && !$thing instanceof \Countable) {
            return strlen((string) $thing);
        }
        if ($thing instanceof \Countable || is_array($thing)) {
            return count($thing);
        }
        if ($thing instanceof \IteratorAggregate) {
            return iterator_count($thing);
        }
        return 1;
    }
    function twig_title_string_filter(Twig_Environment $env, $string)
    {
        return ucwords(strtolower($string));
    }
    function twig_capitalize_string_filter(Twig_Environment $env, $string)
    {
        return ucfirst(strtolower($string));
    }
}
function twig_ensure_traversable($seq)
{
    if ($seq instanceof Traversable || is_array($seq)) {
        return $seq;
    }
    return [];
}
function twig_test_empty($value)
{
    if ($value instanceof Countable) {
        return 0 == count($value);
    }
    if (is_object($value) && method_exists($value, '__toString')) {
        return '' === (string) $value;
    }
    return '' === $value || false === $value || null === $value || [] === $value;
}
function twig_test_iterable($value)
{
    return $value instanceof Traversable || is_array($value);
}
function twig_include(Twig_Environment $env, $context, $template, $variables = [], $withContext = true, $ignoreMissing = false, $sandboxed = false)
{
    $alreadySandboxed = false;
    $sandbox = null;
    if ($withContext) {
        $variables = array_merge($context, $variables);
    }
    if ($isSandboxed = $sandboxed && $env->hasExtension('Twig_Extension_Sandbox')) {
        $sandbox = $env->getExtension('Twig_Extension_Sandbox');
        if (!$alreadySandboxed = $sandbox->isSandboxed()) {
            $sandbox->enableSandbox();
        }
    }
    $result = '';
    try {
        $result = $env->resolveTemplate($template)->render($variables);
    } catch (Twig_Error_Loader $e) {
        if (!$ignoreMissing) {
            if ($isSandboxed && !$alreadySandboxed) {
                $sandbox->disableSandbox();
            }
            throw $e;
        }
    } catch (Throwable $e) {
        if ($isSandboxed && !$alreadySandboxed) {
            $sandbox->disableSandbox();
        }
        throw $e;
    } catch (Exception $e) {
        if ($isSandboxed && !$alreadySandboxed) {
            $sandbox->disableSandbox();
        }
        throw $e;
    }
    if ($isSandboxed && !$alreadySandboxed) {
        $sandbox->disableSandbox();
    }
    return $result;
}
function twig_source(Twig_Environment $env, $name, $ignoreMissing = false)
{
    $loader = $env->getLoader();
    try {
        if (!$loader instanceof Twig_SourceContextLoaderInterface) {
            return $loader->getSource($name);
        } else {
            return $loader->getSourceContext($name)->getCode();
        }
    } catch (Twig_Error_Loader $e) {
        if (!$ignoreMissing) {
            throw $e;
        }
    }
}
function twig_constant($constant, $object = null)
{
    if (null !== $object) {
        $constant = get_class($object) . '::' . $constant;
    }
    return constant($constant);
}
function twig_constant_is_defined($constant, $object = null)
{
    if (null !== $object) {
        $constant = get_class($object) . '::' . $constant;
    }
    return defined($constant);
}
function twig_array_batch($items, $size, $fill = null)
{
    if ($items instanceof Traversable) {
        $items = iterator_to_array($items, false);
    }
    $size = ceil($size);
    $result = array_chunk($items, $size, true);
    if (null !== $fill && !empty($result)) {
        $last = count($result) - 1;
        if ($fillCount = $size - count($result[$last])) {
            $result[$last] = array_merge(
                $result[$last],
                array_fill(0, $fillCount, $fill)
            );
        }
    }
    return $result;
}
class_alias('Twig_Extension_Core', 'Twig\Extension\CoreExtension', false);
