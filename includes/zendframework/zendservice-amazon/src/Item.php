<?php
namespace ZendService\Amazon;
use DOMElement;
use DOMXPath;
class Item
{
    public $ASIN;
    public $DetailPageURL;
    public $SalesRank;
    public $TotalReviews;
    public $AverageRating;
    public $SmallImage;
    public $MediumImage;
    public $LargeImage;
    public $Subjects;
    public $Offers;
    public $CustomerReviews = [];
    public $SimilarProducts = [];
    public $Accessories = [];
    public $Tracks = [];
    public $ListmaniaLists = [];
    public $BrowseNodes = [];
    protected $_dom;
    public function __construct(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . Amazon::getVersion());
        $this->ASIN = $xpath->query('./az:ASIN/text()', $dom)->item(0)->data;
        $result = $xpath->query('./az:DetailPageURL/text()', $dom);
        if ($result->length == 1) {
            $this->DetailPageURL = $result->item(0)->data;
        }
        if ($xpath->query('./az:ItemAttributes/az:ListPrice', $dom)->length >= 1) {
            $this->CurrencyCode = (string) $xpath
                ->query('./az:ItemAttributes/az:ListPrice/az:CurrencyCode/text()', $dom)->item(0)->data;
            $this->Amount = (int) $xpath
                ->query('./az:ItemAttributes/az:ListPrice/az:Amount/text()', $dom)->item(0)->data;
            $this->FormattedPrice = (string) $xpath
                ->query('./az:ItemAttributes/az:ListPrice/az:FormattedPrice/text()', $dom)->item(0)->data;
        }
        $result = $xpath->query('./az:ItemAttributes/az:*/text()', $dom);
        if ($result->length >= 1) {
            foreach ($result as $v) {
                if (isset($this->{$v->parentNode->tagName})) {
                    if (is_array($this->{$v->parentNode->tagName})) {
                        array_push($this->{$v->parentNode->tagName}, (string) $v->data);
                    } else {
                        $this->{$v->parentNode->tagName} = [$this->{$v->parentNode->tagName}, (string) $v->data];
                    }
                } else {
                    $this->{$v->parentNode->tagName} = (string) $v->data;
                }
            }
        }
        foreach (['SmallImage', 'MediumImage', 'LargeImage'] as $im) {
            $result = $xpath->query("./az:ImageSets/az:ImageSet[@Category='primary']/az:$im", $dom);
            if ($result->length == 1) {
                $this->$im = new Image($result->item(0));
                $this->$im->Url = (string) $this->$im->Url;
            }
        }
        foreach (['MediumImage', 'LargeImage'] as $im) {
            $result = $xpath->query("./az:ImageSets/az:ImageSet[@Category='variant']/az:$im", $dom);
            if ($result->length >= 1) {
                $values = [];
                foreach ($result as $v) {
                    $image = new Image($v);
                    $image->Url = (string) $image->Url;
                    $values[] = $image;
                }
                $this->{$im . 's'} = $values;
            }
        }
        $result = $xpath->query('./az:ItemAttributes/az:ItemDimensions', $dom);
        if ($result->length == 1) {
            $this->ItemDimensions = new ItemDimensions($result->item(0));
        }
        $result = $xpath->query('./az:ItemAttributes/az:PackageDimensions', $dom);
        if ($result->length == 1) {
            $this->PackageDimensions = new PackageDimensions($result->item(0));
        }
        $result = $xpath->query('./az:SalesRank/text()', $dom);
        if ($result->length == 1) {
            $this->SalesRank = (int) $result->item(0)->data;
        }
        foreach ([
            'Director' => 'AttributeDirector',
            'Studio' => 'AttributeStudio',
            'IsAdultProduct' => 'AttributeIsAdultProduct',
          ] as $attr => $prop) {
            $result = $xpath->query("./az:ItemAttributes/az:{$attr}/text()", $dom);
            if ($result->length == 1) {
                $this->$prop = (string) $result->item(0)->data;
            }
        }
        $result = $xpath->query('./az:CustomerReviews/az:Review', $dom);
        if ($result->length >= 1) {
            foreach ($result as $review) {
                $this->CustomerReviews[] = new CustomerReview($review);
            }
            $this->AverageRating = (float) $xpath
                ->query('./az:CustomerReviews/az:AverageRating/text()', $dom)->item(0)->data;
            $this->TotalReviews = (int) $xpath
                ->query('./az:CustomerReviews/az:TotalReviews/text()', $dom)->item(0)->data;
        }
        $result = $xpath->query('./az:CustomerReviews/az:HasReviews/text()', $dom);
        if ($result->length == 1) {
            $this->HasReviews = ($result->item(0)->data  == 'true');
        }
        $result = $xpath->query('./az:CustomerReviews/az:IFrameURL/text()', $dom);
        if ($result->length == 1) {
            $this->IFrameURL = $result->item(0)->data;
        }
        $result = $xpath->query('./az:EditorialReviews/az:*', $dom);
        if ($result->length >= 1) {
            foreach ($result as $r) {
                $this->EditorialReviews[] = new EditorialReview($r);
            }
        }
        $result = $xpath->query('./az:SimilarProducts/az:*', $dom);
        if ($result->length >= 1) {
            foreach ($result as $r) {
                $this->SimilarProducts[] = new SimilarProduct($r);
            }
        }
        $result = $xpath->query('./az:ListmaniaLists/*', $dom);
        if ($result->length >= 1) {
            foreach ($result as $r) {
                $this->ListmaniaLists[] = new ListmaniaList($r);
            }
        }
        $result = $xpath->query('./az:Tracks/az:Disc', $dom);
        if ($result->length > 1) {
            foreach ($result as $disk) {
                foreach ($xpath->query('./*/text()', $disk) as $t) {
                    $this->Tracks[] = (string) $t->data;
                }
            }
        } elseif ($result->length == 1) {
            foreach ($xpath->query('./*/text()', $result->item(0)) as $t) {
                $this->Tracks[] = (string) $t->data;
            }
        }
        $result = $xpath->query('./az:Offers', $dom);
        $resultSummary = $xpath->query('./az:OfferSummary', $dom);
        if ($result->length > 1 || $resultSummary->length == 1) {
            $this->Offers = new OfferSet($dom);
        }
        $result = $xpath->query('./az:Accessories/*', $dom);
        if ($result->length > 1) {
            foreach ($result as $r) {
                $this->Accessories[] = new Accessories($r);
            }
        }
        $result = $xpath->query('./az:BrowseNodes/az:BrowseNode', $dom);
        if ($result->length >= 1) {
            foreach ($result as $node) {
                $node = new BrowseNode($node);
                $this->BrowseNodes[$node->BrowseNodeId] = $node->Name;
            }
        }
        $this->_dom = $dom;
    }
    public function asXml()
    {
        return $this->_dom->ownerDocument->saveXML($this->_dom);
    }
}
