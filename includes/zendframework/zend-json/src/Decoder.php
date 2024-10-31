<?php
namespace Zend\Json;
use stdClass;
use Zend\Json\Exception\InvalidArgumentException;
use Zend\Json\Exception\RuntimeException;
class Decoder
{
    const EOF       = 0;
    const DATUM     = 1;
    const LBRACE    = 2;
    const LBRACKET  = 3;
    const RBRACE    = 4;
    const RBRACKET  = 5;
    const COMMA     = 6;
    const COLON     = 7;
    protected $source;
    protected $sourceLength;
    protected $offset;
    protected $token;
    protected $decodeType;
    protected $tokenValue;
    public static function decodeUnicodeString($chrs)
    {
        $chrs       = (string) $chrs;
        $utf8       = '';
        $strlenChrs = strlen($chrs);
        for ($i = 0; $i < $strlenChrs; $i++) {
            $ordChrsC = ord($chrs[$i]);
            switch (true) {
                case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $i, 6)):
                                        $utf16 = chr(hexdec(substr($chrs, ($i + 2), 2)))
                           . chr(hexdec(substr($chrs, ($i + 4), 2)));
                    $utf8char = self::utf162utf8($utf16);
                    $search  = ['\\', "\n", "\t", "\r", chr(0x08), chr(0x0C), '"', '\'', '/'];
                    if (in_array($utf8char, $search)) {
                        $replace = ['\\\\', '\\n', '\\t', '\\r', '\\b', '\\f', '\\"', '\\\'', '\\/'];
                        $utf8char  = str_replace($search, $replace, $utf8char);
                    }
                    $utf8 .= $utf8char;
                    $i += 5;
                    break;
                case ($ordChrsC >= 0x20) && ($ordChrsC <= 0x7F):
                    $utf8 .= $chrs{$i};
                    break;
                case ($ordChrsC & 0xE0) == 0xC0:
                                                            $utf8 .= substr($chrs, $i, 2);
                    ++$i;
                    break;
                case ($ordChrsC & 0xF0) == 0xE0:
                                                            $utf8 .= substr($chrs, $i, 3);
                    $i += 2;
                    break;
                case ($ordChrsC & 0xF8) == 0xF0:
                                                            $utf8 .= substr($chrs, $i, 4);
                    $i += 3;
                    break;
                case ($ordChrsC & 0xFC) == 0xF8:
                                                            $utf8 .= substr($chrs, $i, 5);
                    $i += 4;
                    break;
                case ($ordChrsC & 0xFE) == 0xFC:
                                                            $utf8 .= substr($chrs, $i, 6);
                    $i += 5;
                    break;
            }
        }
        return $utf8;
    }
    protected function __construct($source, $decodeType)
    {
        $this->source       = self::decodeUnicodeString($source);
        $this->sourceLength = strlen($this->source);
        $this->token        = self::EOF;
        $this->offset       = 0;
        switch ($decodeType) {
            case Json::TYPE_ARRAY:
            case Json::TYPE_OBJECT:
                $this->decodeType = $decodeType;
                break;
            default:
                throw new InvalidArgumentException(sprintf(
                    'Unknown decode type "%s", please use one of the Json::TYPE_* constants',
                    $decodeType
                ));
        }
        $this->getNextToken();
    }
    public static function decode($source, $objectDecodeType = Json::TYPE_OBJECT)
    {
        $decoder = new static($source, $objectDecodeType);
        return $decoder->decodeValue();
    }
    protected function decodeValue()
    {
        switch ($this->token) {
            case self::DATUM:
                $result  = $this->tokenValue;
                $this->getNextToken();
                return $result;
            case self::LBRACE:
                return $this->decodeObject();
            case self::LBRACKET:
                return $this->decodeArray();
            default:
                return;
        }
    }
    protected function decodeObject()
    {
        $members = [];
        $tok     = $this->getNextToken();
        while ($tok && $tok !== self::RBRACE) {
            if ($tok !== self::DATUM || ! is_string($this->tokenValue)) {
                throw new RuntimeException(sprintf('Missing key in object encoding: %s', $this->source));
            }
            $key = $this->tokenValue;
            $tok = $this->getNextToken();
            if ($tok !== self::COLON) {
                throw new RuntimeException(sprintf('Missing ":" in object encoding: %s', $this->source));
            }
            $this->getNextToken();
            $members[$key] = $this->decodeValue();
            $tok = $this->token;
            if ($tok === self::RBRACE) {
                break;
            }
            if ($tok !== self::COMMA) {
                throw new RuntimeException(sprintf('Missing "," in object encoding: %s', $this->source));
            }
            $tok = $this->getNextToken();
        }
        switch ($this->decodeType) {
            case Json::TYPE_OBJECT:
                                $result = new stdClass();
                foreach ($members as $key => $value) {
                    if ($key === '') {
                        $key = '_empty_';
                    }
                    $result->$key = $value;
                }
                break;
            case Json::TYPE_ARRAY:
                            default:
                $result = $members;
                break;
        }
        $this->getNextToken();
        return $result;
    }
    protected function decodeArray()
    {
        $result = [];
        $tok    = $this->getNextToken();
        $index  = 0;
        while ($tok && $tok !== self::RBRACKET) {
            $result[$index++] = $this->decodeValue();
            $tok = $this->token;
            if ($tok == self::RBRACKET || ! $tok) {
                break;
            }
            if ($tok !== self::COMMA) {
                throw new RuntimeException(sprintf('Missing "," in array encoding: %s', $this->source));
            }
            $tok = $this->getNextToken();
        }
        $this->getNextToken();
        return $result;
    }
    protected function eatWhitespace()
    {
        if (preg_match('/([\t\b\f\n\r ])*/s', $this->source, $matches, PREG_OFFSET_CAPTURE, $this->offset)
            && $matches[0][1] == $this->offset
        ) {
            $this->offset += strlen($matches[0][0]);
        }
    }
    protected function getNextToken()
    {
        $this->token      = self::EOF;
        $this->tokenValue = null;
        $this->eatWhitespace();
        if ($this->offset >= $this->sourceLength) {
            return self::EOF;
        }
        $str       = $this->source;
        $strLength = $this->sourceLength;
        $i         = $this->offset;
        $start     = $i;
        switch ($str{$i}) {
            case '{':
                $this->token = self::LBRACE;
                break;
            case '}':
                $this->token = self::RBRACE;
                break;
            case '[':
                $this->token = self::LBRACKET;
                break;
            case ']':
                $this->token = self::RBRACKET;
                break;
            case ',':
                $this->token = self::COMMA;
                break;
            case ':':
                $this->token = self::COLON;
                break;
            case '"':
                $result = '';
                do {
                    $i++;
                    if ($i >= $strLength) {
                        break;
                    }
                    $chr = $str{$i};
                    if ($chr === '"') {
                        break;
                    }
                    if ($chr !== '\\') {
                        $result .= $chr;
                        continue;
                    }
                    $i++;
                    if ($i >= $strLength) {
                        break;
                    }
                    $chr = $str{$i};
                    switch ($chr) {
                        case '"':
                            $result .= '"';
                            break;
                        case '\\':
                            $result .= '\\';
                            break;
                        case '/':
                            $result .= '/';
                            break;
                        case 'b':
                            $result .= "\x08";
                            break;
                        case 'f':
                            $result .= "\x0c";
                            break;
                        case 'n':
                            $result .= "\x0a";
                            break;
                        case 'r':
                            $result .= "\x0d";
                            break;
                        case 't':
                            $result .= "\x09";
                            break;
                        case '\'':
                            $result .= '\'';
                            break;
                        default:
                            throw new RuntimeException(sprintf('Illegal escape sequence "%s"', $chr));
                    }
                } while ($i < $strLength);
                $this->token = self::DATUM;
                $this->tokenValue = $result;
                break;
            case 't':
                if (($i + 3) < $strLength && substr($str, $start, 4) === 'true') {
                    $this->token = self::DATUM;
                }
                $this->tokenValue = true;
                $i += 3;
                break;
            case 'f':
                if (($i + 4) < $strLength && substr($str, $start, 5) === 'false') {
                    $this->token = self::DATUM;
                }
                $this->tokenValue = false;
                $i += 4;
                break;
            case 'n':
                if (($i + 3) < $strLength && substr($str, $start, 4) === 'null') {
                    $this->token = self::DATUM;
                }
                $this->tokenValue = null;
                $i += 3;
                break;
        }
        if ($this->token !== self::EOF) {
            $this->offset = $i + 1;
            return $this->token;
        }
        $chr = $str{$i};
        if ($chr !== '-' && $chr !== '.' && ($chr < '0' || $chr > '9')) {
            throw new RuntimeException('Illegal Token');
        }
        if (preg_match('/-?([0-9])*(\.[0-9]*)?((e|E)((-|\+)?)[0-9]+)?/s', $str, $matches, PREG_OFFSET_CAPTURE, $start)
            && $matches[0][1] == $start
        ) {
            $datum = $matches[0][0];
            if (! is_numeric($datum)) {
                throw new RuntimeException(sprintf('Illegal number format: %s', $datum));
            }
            if (preg_match('/^0\d+$/', $datum)) {
                throw new RuntimeException(sprintf('Octal notation not supported by JSON (value: %o)', $datum));
            }
            $val  = intval($datum);
            $fVal = floatval($datum);
            $this->tokenValue = ($val == $fVal ? $val : $fVal);
            $this->token = self::DATUM;
            $this->offset = $start + strlen($datum);
        }
        return $this->token;
    }
    protected static function utf162utf8($utf16)
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        }
        $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});
        switch (true) {
            case (0x7F & $bytes) == $bytes:
                                                return chr(0x7F & $bytes);
            case (0x07FF & $bytes) == $bytes:
                                                return chr(0xC0 | (($bytes >> 6) & 0x1F))
                    . chr(0x80 | ($bytes & 0x3F));
            case (0xFFFF & $bytes) == $bytes:
                                                return chr(0xE0 | (($bytes >> 12) & 0x0F))
                    . chr(0x80 | (($bytes >> 6) & 0x3F))
                    . chr(0x80 | ($bytes & 0x3F));
        }
        return '';
    }
}
