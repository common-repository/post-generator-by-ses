<?php
namespace Zend\Escaper;
class Escaper
{
    protected static $htmlNamedEntityMap = [
        34 => 'quot',                 38 => 'amp',                  60 => 'lt',                   62 => 'gt',               ];
    protected $encoding = 'utf-8';
    protected $htmlSpecialCharsFlags;
    protected $htmlAttrMatcher;
    protected $jsMatcher;
    protected $cssMatcher;
    protected $supportedEncodings = [
        'iso-8859-1',   'iso8859-1',    'iso-8859-5',   'iso8859-5',
        'iso-8859-15',  'iso8859-15',   'utf-8',        'cp866',
        'ibm866',       '866',          'cp1251',       'windows-1251',
        'win-1251',     '1251',         'cp1252',       'windows-1252',
        '1252',         'koi8-r',       'koi8-ru',      'koi8r',
        'big5',         '950',          'gb2312',       '936',
        'big5-hkscs',   'shift_jis',    'sjis',         'sjis-win',
        'cp932',        '932',          'euc-jp',       'eucjp',
        'eucjp-win',    'macroman'
    ];
    public function __construct($encoding = null)
    {
        if ($encoding !== null) {
            if (! is_string($encoding)) {
                throw new Exception\InvalidArgumentException(
                    get_class($this) . ' constructor parameter must be a string, received ' . gettype($encoding)
                );
            }
            if ($encoding === '') {
                throw new Exception\InvalidArgumentException(
                    get_class($this) . ' constructor parameter does not allow a blank value'
                );
            }
            $encoding = strtolower($encoding);
            if (! in_array($encoding, $this->supportedEncodings)) {
                throw new Exception\InvalidArgumentException(
                    'Value of \'' . $encoding . '\' passed to ' . get_class($this)
                    . ' constructor parameter is invalid. Provide an encoding supported by htmlspecialchars()'
                );
            }
            $this->encoding = $encoding;
        }
        $this->htmlSpecialCharsFlags = ENT_QUOTES | ENT_SUBSTITUTE;
        $this->htmlAttrMatcher = [$this, 'htmlAttrMatcher'];
        $this->jsMatcher       = [$this, 'jsMatcher'];
        $this->cssMatcher      = [$this, 'cssMatcher'];
    }
    public function getEncoding()
    {
        return $this->encoding;
    }
    public function escapeHtml($string)
    {
        return htmlspecialchars($string, $this->htmlSpecialCharsFlags, $this->encoding);
    }
    public function escapeHtmlAttr($string)
    {
        $string = $this->toUtf8($string);
        if ($string === '' || ctype_digit($string)) {
            return $string;
        }
        $result = preg_replace_callback('/[^a-z0-9,\.\-_]/iSu', $this->htmlAttrMatcher, $string);
        return $this->fromUtf8($result);
    }
    public function escapeJs($string)
    {
        $string = $this->toUtf8($string);
        if ($string === '' || ctype_digit($string)) {
            return $string;
        }
        $result = preg_replace_callback('/[^a-z0-9,\._]/iSu', $this->jsMatcher, $string);
        return $this->fromUtf8($result);
    }
    public function escapeUrl($string)
    {
        return rawurlencode($string);
    }
    public function escapeCss($string)
    {
        $string = $this->toUtf8($string);
        if ($string === '' || ctype_digit($string)) {
            return $string;
        }
        $result = preg_replace_callback('/[^a-z0-9]/iSu', $this->cssMatcher, $string);
        return $this->fromUtf8($result);
    }
    protected function htmlAttrMatcher($matches)
    {
        $chr = $matches[0];
        $ord = ord($chr);
        if (($ord <= 0x1f && $chr != "\t" && $chr != "\n" && $chr != "\r")
            || ($ord >= 0x7f && $ord <= 0x9f)
        ) {
            return '&#xFFFD;';
        }
        if (strlen($chr) > 1) {
            $chr = $this->convertEncoding($chr, 'UTF-32BE', 'UTF-8');
        }
        $hex = bin2hex($chr);
        $ord = hexdec($hex);
        if (isset(static::$htmlNamedEntityMap[$ord])) {
            return '&' . static::$htmlNamedEntityMap[$ord] . ';';
        }
        if ($ord > 255) {
            return sprintf('&#x%04X;', $ord);
        }
        return sprintf('&#x%02X;', $ord);
    }
    protected function jsMatcher($matches)
    {
        $chr = $matches[0];
        if (strlen($chr) == 1) {
            return sprintf('\\x%02X', ord($chr));
        }
        $chr = $this->convertEncoding($chr, 'UTF-16BE', 'UTF-8');
        $hex = strtoupper(bin2hex($chr));
        if (strlen($hex) <= 4) {
            return sprintf('\\u%04s', $hex);
        }
        $highSurrogate = substr($hex, 0, 4);
        $lowSurrogate = substr($hex, 4, 4);
        return sprintf('\\u%04s\\u%04s', $highSurrogate, $lowSurrogate);
    }
    protected function cssMatcher($matches)
    {
        $chr = $matches[0];
        if (strlen($chr) == 1) {
            $ord = ord($chr);
        } else {
            $chr = $this->convertEncoding($chr, 'UTF-32BE', 'UTF-8');
            $ord = hexdec(bin2hex($chr));
        }
        return sprintf('\\%X ', $ord);
    }
    protected function toUtf8($string)
    {
        if ($this->getEncoding() === 'utf-8') {
            $result = $string;
        } else {
            $result = $this->convertEncoding($string, 'UTF-8', $this->getEncoding());
        }
        if (! $this->isUtf8($result)) {
            throw new Exception\RuntimeException(
                sprintf('String to be escaped was not valid UTF-8 or could not be converted: %s', $result)
            );
        }
        return $result;
    }
    protected function fromUtf8($string)
    {
        if ($this->getEncoding() === 'utf-8') {
            return $string;
        }
        return $this->convertEncoding($string, $this->getEncoding(), 'UTF-8');
    }
    protected function isUtf8($string)
    {
        return $string === '' || preg_match('/^./su', $string);
    }
    protected function convertEncoding($string, $to, $from)
    {
        if (function_exists('iconv')) {
            $result = iconv($from, $to, $string);
        } elseif (function_exists('mb_convert_encoding')) {
            $result = mb_convert_encoding($string, $to, $from);
        } else {
            throw new Exception\RuntimeException(
                get_class($this)
                . ' requires either the iconv or mbstring extension to be installed'
                . ' when escaping for non UTF-8 strings.'
            );
        }
        if ($result === false) {
            return '';
        }
        return $result;
    }
}
