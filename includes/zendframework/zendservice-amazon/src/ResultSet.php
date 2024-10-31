<?php
namespace ZendService\Amazon;
use DOMDocument;
use DOMXPath;
class ResultSet implements \SeekableIterator
{
    protected $_results = null;
    protected $_dom;
    protected $_xpath;
    protected $_currentIndex = 0;
    public function __construct(DOMDocument $dom)
    {
        $this->_dom = $dom;
        $this->_xpath = new DOMXPath($dom);
        $this->_xpath->registerNamespace(
            'az',
            'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion()
        );
        $this->_results = $this->_xpath->query('//az:Item');
    }
    public function totalResults()
    {
        $result = $this->_xpath->query('//az:TotalResults/text()');
        return (int) (isset($result->item(0)->data) ? $result->item(0)->data : 0);
    }
    public function totalPages()
    {
        $result = $this->_xpath->query('//az:TotalPages/text()');
        return (int) (isset($result->item(0)->data) ? $result->item(0)->data : 0);
    }
    public function current()
    {
        $dom = $this->_results->item($this->_currentIndex);
        if ($dom === null) {
            throw new Exception\RuntimeException('no results found');
        }
        return new Item($dom);
    }
    public function key()
    {
        return $this->_currentIndex;
    }
    public function next()
    {
        $this->_currentIndex += 1;
    }
    public function rewind()
    {
        $this->_currentIndex = 0;
    }
    public function seek($index)
    {
        $indexInt = (int) $index;
        if ($indexInt >= 0 && (null === $this->_results || $indexInt < $this->_results->length)) {
            $this->_currentIndex = $indexInt;
        } else {
            throw new Exception\OutOfBoundsException("Illegal index '$index'");
        }
    }
    public function valid()
    {
        return null !== $this->_results && $this->_currentIndex < $this->_results->length;
    }
    public function hasError()
    {
        return $this->_xpath->evaluate('name(/*)') == 'ItemSearchErrorResponse';
    }
}
