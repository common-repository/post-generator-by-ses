<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMXPath;
class CustomerReview
{
    public $Rating;
    public $HelpfulVotes;
    public $CustomerId;
    public $TotalVotes;
    public $Date;
    public $Summary;
    public $Content;
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        foreach (['Rating', 'HelpfulVotes', 'CustomerId', 'TotalVotes', 'Date', 'Summary', 'Content'] as $el) {
            $result = $xpath->query("./az:$el/text()", $dom);
            if ($result->length == 1) {
                $this->$el = (string) $result->item(0)->data;
            }
        }
    }
}
