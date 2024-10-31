<?php
namespace ZendRest\Client;
use IteratorAggregate;
use ZendXml\Security as XmlSecurity;
class Result implements IteratorAggregate
{
    protected $_sxml;
    protected $_errstr;
    public function __construct($data)
    {
        set_error_handler([$this, 'handleXmlErrors']);
        $this->_sxml = XmlSecurity::scan($data);
        restore_error_handler();
        if ($this->_sxml === false) {
            if ($this->_errstr === null) {
                $message = 'An error occured while parsing the REST response with simplexml.';
            } else {
                $message = 'REST Response Error: ' . $this->_errstr;
                $this->_errstr = null;
            }
            throw new Exception\ResultException($message);
        }
    }
    public function handleXmlErrors($errno, $errstr, $errfile = null, $errline = null, array $errcontext = null)
    {
        $this->_errstr = $errstr;
        return true;
    }
    public function toValue(\SimpleXMLElement $value)
    {
        $node = dom_import_simplexml($value);
        return $node->nodeValue;
    }
    public function __get($name)
    {
        if (isset($this->_sxml->{$name})) {
            return $this->_sxml->{$name};
        }
        $result = $this->_sxml->xpath("//$name");
        $count  = count($result);
        if ($count == 0) {
            return null;
        } elseif ($count == 1) {
            return $result[0];
        }
        return $result;
    }
    public function __call($method, $args)
    {
        if (null !== ($value = $this->__get($method))) {
            if (!is_array($value)) {
                return $this->toValue($value);
            }
            $return = [];
            foreach ($value as $element) {
                $return[] = $this->toValue($element);
            }
            return $return;
        }
        return null;
    }
    public function __isset($name)
    {
        if (isset($this->_sxml->{$name})) {
            return true;
        }
        $result = $this->_sxml->xpath("//$name");
        if (count($result) > 0) {
            return true;
        }
        return false;
    }
    public function getIterator()
    {
        return $this->_sxml;
    }
    public function getStatus()
    {
        $status = $this->_sxml->xpath('//status/text()');
        $status = strtolower($status[0]);
        if (ctype_alpha($status) && $status == 'success') {
            return true;
        } elseif (ctype_alpha($status) && $status != 'success') {
            return false;
        }
        return (bool) $status;
    }
    public function isError()
    {
        $status = $this->getStatus();
        if ($status) {
            return false;
        }
        return true;
    }
    public function isSuccess()
    {
        $status = $this->getStatus();
        if ($status) {
            return true;
        }
        return false;
    }
    public function __toString()
    {
        if (!$this->getStatus()) {
            $message = $this->_sxml->xpath('//message');
            return (string) $message[0];
        }
        $result = $this->_sxml->xpath('//response');
        if (count($result) > 1) {
            return (string) 'An error occured.';
        } else {
            return (string) $result[0];
        }
    }
}
