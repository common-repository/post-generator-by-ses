<?php
namespace Zend\Http;
use ArrayIterator;
use Countable;
use Iterator;
use Traversable;
use Zend\Http\Header\Exception;
use Zend\Http\Header\GenericHeader;
use Zend\Loader\PluginClassLocator;
class Headers implements Countable, Iterator
{
    protected $pluginClassLoader;
    protected $headersKeys = [];
    protected $headers = [];
    public static function fromString($string)
    {
        $headers   = new static();
        $current   = [];
        $emptyLine = 0;
                foreach (explode("\r\n", $string) as $line) {
                                    if (preg_match('/^\s*$/', $line)) {
                                $emptyLine += 1;
                if ($emptyLine > 2) {
                    throw new Exception\RuntimeException('Malformed header detected');
                }
                continue;
            }
            if ($emptyLine) {
                throw new Exception\RuntimeException('Malformed header detected');
            }
                        if (preg_match('/^(?P<name>[^()><@,;:\"\\/\[\]?={} \t]+):.*$/', $line, $matches)) {
                if ($current) {
                                        $headers->headersKeys[] = static::createKey($current['name']);
                    $headers->headers[]     = $current;
                }
                $current = [
                    'name' => $matches['name'],
                    'line' => trim($line),
                ];
                continue;
            }
            if (preg_match("/^[ \t][^\r\n]*$/", $line, $matches)) {
                                $current['line'] .= trim($line);
                continue;
            }
                        throw new Exception\RuntimeException(sprintf(
                'Line "%s" does not match header format!',
                $line
            ));
        }
        if ($current) {
            $headers->headersKeys[] = static::createKey($current['name']);
            $headers->headers[]     = $current;
        }
        return $headers;
    }
    public function setPluginClassLoader(PluginClassLocator $pluginClassLoader)
    {
        $this->pluginClassLoader = $pluginClassLoader;
        return $this;
    }
    public function getPluginClassLoader()
    {
        if ($this->pluginClassLoader === null) {
            $this->pluginClassLoader = new HeaderLoader();
        }
        return $this->pluginClassLoader;
    }
    public function addHeaders($headers)
    {
        if (! is_array($headers) && ! $headers instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expected array or Traversable; received "%s"',
                (is_object($headers) ? get_class($headers) : gettype($headers))
            ));
        }
        foreach ($headers as $name => $value) {
            if (is_int($name)) {
                if (is_string($value)) {
                    $this->addHeaderLine($value);
                } elseif (is_array($value) && count($value) == 1) {
                    $this->addHeaderLine(key($value), current($value));
                } elseif (is_array($value) && count($value) == 2) {
                    $this->addHeaderLine($value[0], $value[1]);
                } elseif ($value instanceof Header\HeaderInterface) {
                    $this->addHeader($value);
                }
            } elseif (is_string($name)) {
                $this->addHeaderLine($name, $value);
            }
        }
        return $this;
    }
    public function addHeaderLine($headerFieldNameOrLine, $fieldValue = null)
    {
        $matches = null;
        if (preg_match('/^(?P<name>[^()><@,;:\"\\/\[\]?=}{ \t]+):.*$/', $headerFieldNameOrLine, $matches)
            && $fieldValue === null) {
                        $headerName = $matches['name'];
            $headerKey  = static::createKey($matches['name']);
            $line = $headerFieldNameOrLine;
        } elseif ($fieldValue === null) {
            throw new Exception\InvalidArgumentException('A field name was provided without a field value');
        } else {
            $headerName = $headerFieldNameOrLine;
            $headerKey  = static::createKey($headerFieldNameOrLine);
            if (is_array($fieldValue)) {
                $fieldValue = implode(', ', $fieldValue);
            }
            $line = $headerFieldNameOrLine . ': ' . $fieldValue;
        }
        $this->headersKeys[] = $headerKey;
        $this->headers[]     = ['name' => $headerName, 'line' => $line];
        return $this;
    }
    public function addHeader(Header\HeaderInterface $header)
    {
        $key = static::createKey($header->getFieldName());
        $index = array_search($key, $this->headersKeys);
                if ($index === false) {
            $this->headersKeys[] = $key;
            $this->headers[]     = $header;
            return $this;
        }
                        $class = ($this->getPluginClassLoader()->load(str_replace('-', '', $key))) ?: Header\GenericHeader::class;
        if (in_array(Header\MultipleHeaderInterface::class, class_implements($class, true))) {
            $this->headersKeys[] = $key;
            $this->headers[] = $header;
            return $this;
        }
                $this->headers[$index] = $header;
        return $this;
    }
    public function removeHeader(Header\HeaderInterface $header)
    {
        $index = array_search($header, $this->headers, true);
        if ($index !== false) {
            unset($this->headersKeys[$index]);
            unset($this->headers[$index]);
            return true;
        }
        return false;
    }
    public function clearHeaders()
    {
        $this->headers = $this->headersKeys = [];
        return $this;
    }
    public function get($name)
    {
        $key = static::createKey($name);
        if (! $this->has($name)) {
            return false;
        }
        $class = ($this->getPluginClassLoader()->load(str_replace('-', '', $key))) ?: 'Zend\Http\Header\GenericHeader';
        if (in_array('Zend\Http\Header\MultipleHeaderInterface', class_implements($class, true))) {
            $headers = [];
            foreach (array_keys($this->headersKeys, $key) as $index) {
                if (is_array($this->headers[$index])) {
                    $this->lazyLoadHeader($index);
                }
            }
            foreach (array_keys($this->headersKeys, $key) as $index) {
                $headers[] = $this->headers[$index];
            }
            return new ArrayIterator($headers);
        }
        $index = array_search($key, $this->headersKeys);
        if ($index === false) {
            return false;
        }
        if (is_array($this->headers[$index])) {
            return $this->lazyLoadHeader($index);
        }
        return $this->headers[$index];
    }
    public function has($name)
    {
        return in_array(static::createKey($name), $this->headersKeys);
    }
    public function next()
    {
        next($this->headers);
    }
    public function key()
    {
        return (key($this->headers));
    }
    public function valid()
    {
        return (current($this->headers) !== false);
    }
    public function rewind()
    {
        reset($this->headers);
    }
    public function current()
    {
        $current = current($this->headers);
        if (is_array($current)) {
            $current = $this->lazyLoadHeader(key($this->headers));
        }
        return $current;
    }
    public function count()
    {
        return count($this->headers);
    }
    public function toString()
    {
        $headers = '';
        foreach ($this->toArray() as $fieldName => $fieldValue) {
            if (is_array($fieldValue)) {
                                foreach ($fieldValue as $value) {
                    $headers .= $fieldName . ': ' . $value . "\r\n";
                }
                continue;
            }
                        $headers .= $fieldName . ': ' . $fieldValue . "\r\n";
        }
        return $headers;
    }
    public function toArray()
    {
        $headers = [];
        foreach ($this->headers as $header) {
            if ($header instanceof Header\MultipleHeaderInterface) {
                $name = $header->getFieldName();
                if (! isset($headers[$name])) {
                    $headers[$name] = [];
                }
                $headers[$name][] = $header->getFieldValue();
            } elseif ($header instanceof Header\HeaderInterface) {
                $headers[$header->getFieldName()] = $header->getFieldValue();
            } else {
                $matches = null;
                preg_match('/^(?P<name>[^()><@,;:\"\\/\[\]?=}{ \t]+):\s*(?P<value>.*)$/', $header['line'], $matches);
                if ($matches) {
                    $headers[$matches['name']] = $matches['value'];
                }
            }
        }
        return $headers;
    }
    public function forceLoading()
    {
        foreach ($this as $item) {
                    }
        return true;
    }
    protected function lazyLoadHeader($index, $isGeneric = false)
    {
        $current = $this->headers[$index];
        $key = $this->headersKeys[$index];
        $class = $this->getPluginClassLoader()->load(str_replace('-', '', $key));
        if ($isGeneric || ! $class) {
            $class = GenericHeader::class;
        }
        try {
            $headers = $class::fromString($current['line']);
        } catch (Exception\InvalidArgumentException $exception) {
            return $this->lazyLoadHeader($index, true);
        }
        if (is_array($headers)) {
            $this->headers[$index] = $current = array_shift($headers);
            foreach ($headers as $header) {
                $this->headersKeys[] = $key;
                $this->headers[]     = $header;
            }
            return $current;
        }
        $this->headers[$index] = $current = $headers;
        return $current;
    }
    protected static function createKey($name)
    {
        return str_replace(['_', ' ', '.'], '-', strtolower($name));
    }
}
