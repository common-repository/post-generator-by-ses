<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMXPath;
class OfferSet
{
    public $LowestNewPrice;
    public $LowestNewPriceCurrency;
    public $LowestUsedPrice;
    public $LowestUsedPriceCurrency;
    public $TotalNew;
    public $TotalUsed;
    public $TotalCollectible;
    public $TotalRefurbished;
    public $Offers;
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        $offer = $xpath->query('./az:OfferSummary', $dom);
        if ($offer->length == 1) {
            $lowestNewPrice = $xpath->query('./az:OfferSummary/az:LowestNewPrice/az:Amount', $dom);
            if ($lowestNewPrice->length == 1) {
                $this->LowestNewPrice = (int) $xpath
                    ->query('./az:OfferSummary/az:LowestNewPrice/az:Amount/text()', $dom)->item(0)->data;
                $this->LowestNewPriceCurrency = (string) $xpath
                    ->query('./az:OfferSummary/az:LowestNewPrice/az:CurrencyCode/text()', $dom)->item(0)->data;
            }
            $lowestUsedPrice = $xpath->query('./az:OfferSummary/az:LowestUsedPrice/az:Amount', $dom);
            if ($lowestUsedPrice->length == 1) {
                $this->LowestUsedPrice = (int) $xpath
                    ->query('./az:OfferSummary/az:LowestUsedPrice/az:Amount/text()', $dom)->item(0)->data;
                $this->LowestUsedPriceCurrency = (string) $xpath
                    ->query('./az:OfferSummary/az:LowestUsedPrice/az:CurrencyCode/text()', $dom)->item(0)->data;
            }
            $this->TotalNew = (int) $xpath->query('./az:OfferSummary/az:TotalNew/text()', $dom)->item(0)->data;
            $this->TotalUsed = (int) $xpath->query('./az:OfferSummary/az:TotalUsed/text()', $dom)->item(0)->data;
            $this->TotalCollectible = (int) $xpath
                ->query('./az:OfferSummary/az:TotalCollectible/text()', $dom)->item(0)->data;
            $this->TotalRefurbished = (int) $xpath
                ->query('./az:OfferSummary/az:TotalRefurbished/text()', $dom)->item(0)->data;
        }
        $offers = $xpath->query('./az:Offers/az:Offer', $dom);
        if ($offers->length >= 1) {
            foreach ($offers as $offer) {
                $this->Offers[] = new Offer($offer);
            }
        }
    }
}
