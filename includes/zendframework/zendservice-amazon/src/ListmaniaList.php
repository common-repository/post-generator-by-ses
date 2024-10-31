<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMXPath;
class ListmaniaList
{
    public $ListId;
    public $ListName;
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        foreach (['ListId', 'ListName'] as $el) {
            $this->$el = (string) $xpath->query("./az:$el/text()", $dom)->item(0)->data;
        }
    }
}
