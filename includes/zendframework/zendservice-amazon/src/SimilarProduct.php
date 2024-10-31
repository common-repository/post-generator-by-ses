<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMText;
use DOMXPath;
class SimilarProduct
{
    public $ASIN;
    public $Title;
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        foreach (['ASIN', 'Title'] as $el) {
            $text = $xpath->query("./az:$el/text()", $dom)->item(0);
            if ($text instanceof DOMText) {
                $this->$el = (string) $text->data;
            }
        }
    }
}
