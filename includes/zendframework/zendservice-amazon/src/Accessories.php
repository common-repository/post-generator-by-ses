<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMXPath;
class Accessories
{
    public $ASIN;
    public $Title;
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        foreach (['ASIN', 'Title'] as $el) {
            $this->$el = (string) $xpath->query("./az:$el/text()", $dom)->item(0)->data;
        }
    }
}
