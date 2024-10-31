<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMText;
use DOMXPath;
class Offer
{
    public $MerchantId;
    public $MerchantName;
    public $GlancePage;
    public $Condition;
    public $OfferListingId;
    public $Price;
    public $CurrencyCode;
    public $Availability;
    public $IsEligibleForSuperSaverShipping = false;
    public $IsEligibleForPrime = false;
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        $map = [
            'MerchantId'     => './az:Merchant/az:MerchantId/text()',
            'MerchantName'   => './az:Merchant/az:Name/text()',
            'GlancePage'     => './az:Merchant/az:GlancePage/text()',
            'Condition'      => './az:OfferAttributes/az:Condition/text()',
            'OfferListingId' => './az:OfferListing/az:OfferListingId/text()',
            'Price'          => './az:OfferListing/az:Price/az:Amount/text()',
            'CurrencyCode'   => './az:OfferListing/az:Price/az:CurrencyCode/text()',
            'Availability'   => './az:OfferListing/az:Availability/text()',
            'IsEligibleForSuperSaverShipping' => './az:OfferListing/az:IsEligibleForSuperSaverShipping/text()',
            'IsEligibleForPrime' => './az:OfferListing/az:IsEligibleForPrime/text()',
        ];
        foreach ($map as $param_name => $xquery) {
            $query_result = $xpath->query($xquery, $dom);
            if ($query_result->length <= 0) {
                continue;
            }
            $text = $query_result->item(0);
            if (! $text instanceof DOMText) {
                continue;
            }
            $this->$param_name = (string) $text->data;
        }
        if (isset($this->IsEligibleForSuperSaverShipping)) {
            $this->IsEligibleForSuperSaverShipping = (bool) $this->IsEligibleForSuperSaverShipping;
        }
        if (isset($this->IsEligibleForPrime)) {
            $this->IsEligibleForPrime = (bool) $this->IsEligibleForPrime;
        }
        if (isset($this->Price)) {
            $this->Price = (int) $this->Price;
        }
    }
}
