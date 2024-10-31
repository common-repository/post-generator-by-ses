<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMXPath;
class ItemDimensions
{
    public $Weight;
    public $Height;
    public $Width;
    public $Length;
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        foreach (['Height', 'Length', 'Width', 'Weight'] as $prop) {
            $result = $xpath->query('./az:' . $prop . '/text()', $dom);
            if ($result->length == 1) {
                $this->$prop = (int) $result->item(0)->data;
            }
        }
    }
}
