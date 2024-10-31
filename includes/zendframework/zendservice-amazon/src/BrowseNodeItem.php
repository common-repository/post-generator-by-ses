<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMXPath;
class BrowseNodeItem
{
    public $ASIN;
    public $Title;
    protected $_dom;
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        $this->ASIN = $xpath->query('./az:ASIN/text()', $dom)->item(0)->data;
        $this->Title = $xpath->query('./az:Title/text()', $dom)->length > 0
            ? $xpath->query('./az:Title/text()', $dom)->item(0)->data
            : null;
        if (($nodes = $xpath->query('./az:ProductGroup/text()', $dom)) && $nodes->length==1) {
            $this->ProductGroup = $nodes->item(0)->data;
        }
        if (($nodes = $xpath->query('./az:DetailPageURL/text()', $dom)) && $nodes->length==1) {
            $this->DetailPageURL = $nodes->item(0)->data;
        }
        $this->_dom = $dom;
    }
    public function asXml()
    {
        return $this->_dom->ownerDocument->saveXML($this->_dom);
    }
}
