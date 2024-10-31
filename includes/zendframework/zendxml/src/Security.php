<?php
namespace ZendXml;
use DOMDocument;
use SimpleXMLElement;
class Security
{
    const ENTITY_DETECT = 'Detected use of ENTITY in XML, disabled to prevent XXE/XEE attacks';
    protected static function heuristicScan($xml)
    {
        foreach (self::getEntityComparison($xml) as $compare) {
            if (strpos($xml, $compare) !== false) {
                throw new Exception\RuntimeException(self::ENTITY_DETECT);
            }
        }
    }
    public static function scan($xml, DOMDocument $dom = null)
    {
        if (self::isPhpFpm()) {
            self::heuristicScan($xml);
        }
        if (null === $dom) {
            $simpleXml = true;
            $dom = new DOMDocument();
        }
        if (! self::isPhpFpm()) {
            $loadEntities = libxml_disable_entity_loader(true);
            $useInternalXmlErrors = libxml_use_internal_errors(true);
        }
        set_error_handler(function ($errno, $errstr) {
            if (substr_count($errstr, 'DOMDocument::loadXML()') > 0) {
                return true;
            }
            return false;
        }, E_WARNING);
        $result = $dom->loadXml($xml, LIBXML_NONET);
        restore_error_handler();
        if (! $result) {
            if (! self::isPhpFpm()) {
                libxml_disable_entity_loader($loadEntities);
                libxml_use_internal_errors($useInternalXmlErrors);
            }
            return false;
        }
        if (! self::isPhpFpm()) {
            foreach ($dom->childNodes as $child) {
                if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                    if ($child->entities->length > 0) {
                        throw new Exception\RuntimeException(self::ENTITY_DETECT);
                    }
                }
            }
        }
        if (! self::isPhpFpm()) {
            libxml_disable_entity_loader($loadEntities);
            libxml_use_internal_errors($useInternalXmlErrors);
        }
        if (isset($simpleXml)) {
            $result = simplexml_import_dom($dom);
            if (! $result instanceof SimpleXMLElement) {
                return false;
            }
            return $result;
        }
        return $dom;
    }
    public static function scanFile($file, DOMDocument $dom = null)
    {
        if (! file_exists($file)) {
            throw new Exception\InvalidArgumentException(
                "The file $file specified doesn't exist"
            );
        }
        return self::scan(file_get_contents($file), $dom);
    }
    public static function isPhpFpm()
    {
        $isVulnerableVersion = (
            version_compare(PHP_VERSION, '5.5.22', 'lt')
            || (
                version_compare(PHP_VERSION, '5.6', 'gte')
                && version_compare(PHP_VERSION, '5.6.6', 'lt')
            )
        );
        if (substr(php_sapi_name(), 0, 3) === 'fpm' && $isVulnerableVersion) {
            return true;
        }
        return false;
    }
    protected static function getEntityComparison($xml)
    {
        $encodingMap = self::getAsciiEncodingMap();
        return array_map(function ($encoding) use ($encodingMap) {
            $generator   = isset($encodingMap[$encoding]) ? $encodingMap[$encoding] : $encodingMap['UTF-8'];
            return $generator('<!ENTITY');
        }, self::detectXmlEncoding($xml, self::detectStringEncoding($xml)));
    }
    protected static function detectStringEncoding($xml)
    {
        return self::detectBom($xml) ?: self::detectXmlStringEncoding($xml);
    }
    protected static function detectBom($string)
    {
        foreach (self::getBomMap() as $criteria) {
            if (0 === strncmp($string, $criteria['bom'], $criteria['length'])) {
                return $criteria['encoding'];
            }
        }
        return false;
    }
    protected static function detectXmlStringEncoding($xml)
    {
        foreach (self::getAsciiEncodingMap() as $encoding => $generator) {
            $prefix = $generator('<' . '?xml');
            if (0 === strncmp($xml, $prefix, strlen($prefix))) {
                return $encoding;
            }
        }
        return 'UTF-8';
    }
    protected static function detectXmlEncoding($xml, $fileEncoding)
    {
        $encodingMap = self::getAsciiEncodingMap();
        $generator   = $encodingMap[$fileEncoding];
        $encAttr     = $generator('encoding="');
        $quote       = $generator('"');
        $close       = $generator('>');
        $closePos    = strpos($xml, $close);
        if (false === $closePos) {
            return [$fileEncoding];
        }
        $encPos = strpos($xml, $encAttr);
        if (false === $encPos
            || $encPos > $closePos
        ) {
            return [$fileEncoding];
        }
        $encPos   += strlen($encAttr);
        $quotePos = strpos($xml, $quote, $encPos);
        if (false === $quotePos) {
            return [$fileEncoding];
        }
        $encoding = self::substr($xml, $encPos, $quotePos);
        return [
                        str_replace('\0', '', $encoding),             $fileEncoding,                            ];
    }
    protected static function getBomMap()
    {
        return [
            [
                'encoding' => 'UTF-32BE',
                'bom'      => pack('CCCC', 0x00, 0x00, 0xfe, 0xff),
                'length'   => 4,
            ],
            [
                'encoding' => 'UTF-32LE',
                'bom'      => pack('CCCC', 0xff, 0xfe, 0x00, 0x00),
                'length'   => 4,
            ],
            [
                'encoding' => 'GB-18030',
                'bom'      => pack('CCCC', 0x84, 0x31, 0x95, 0x33),
                'length'   => 4,
            ],
            [
                'encoding' => 'UTF-16BE',
                'bom'      => pack('CC', 0xfe, 0xff),
                'length'   => 2,
            ],
            [
                'encoding' => 'UTF-16LE',
                'bom'      => pack('CC', 0xff, 0xfe),
                'length'   => 2,
            ],
            [
                'encoding' => 'UTF-8',
                'bom'      => pack('CCC', 0xef, 0xbb, 0xbf),
                'length'   => 3,
            ],
        ];
    }
    protected static function getAsciiEncodingMap()
    {
        return [
            'UTF-32BE'   => function ($ascii) {
                return preg_replace('/(.)/', "\0\0\0\\1", $ascii);
            },
            'UTF-32LE'   => function ($ascii) {
                return preg_replace('/(.)/', "\\1\0\0\0", $ascii);
            },
            'UTF-32odd1' => function ($ascii) {
                return preg_replace('/(.)/', "\0\\1\0\0", $ascii);
            },
            'UTF-32odd2' => function ($ascii) {
                return preg_replace('/(.)/', "\0\0\\1\0", $ascii);
            },
            'UTF-16BE'   => function ($ascii) {
                return preg_replace('/(.)/', "\0\\1", $ascii);
            },
            'UTF-16LE'   => function ($ascii) {
                return preg_replace('/(.)/', "\\1\0", $ascii);
            },
            'UTF-8'      => function ($ascii) {
                return $ascii;
            },
            'GB-18030'   => function ($ascii) {
                return $ascii;
            },
        ];
    }
    protected static function substr($string, $start, $end)
    {
        $substr = '';
        for ($i = $start; $i < $end; $i += 1) {
            $substr .= $string[$i];
        }
        return $substr;
    }
}
