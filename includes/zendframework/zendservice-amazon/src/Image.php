<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMXPath;
use Zend\Uri;
class Image
{
    public $Url;
    public $Height;
    public $Width;
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        $this->Url    = Uri\UriFactory::factory($xpath->query('./az:URL/text()', $dom)->item(0)->data);
        $this->Height = (int) $xpath->query('./az:Height/text()', $dom)->item(0)->data;
        $this->Width  = (int) $xpath->query('./az:Width/text()', $dom)->item(0)->data;
    }
}
