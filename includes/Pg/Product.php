<?php
namespace Pg;
use Ec\Utils\StringUtils;
class Product
{
    private $asin;
    private $urlInput;
    private $upc;
    private $ean;
    private $urls = [];
    private $titles = [];
    private $descriptions = [];
    private $images = [];
    private $reviews = [];
    private $ratings = [];
    private $youtubeVideos = [];
    private $prices = [];
    public function __construct()
    {
    }
    public function addUrl($url, $site)
    {
        $this->urls[$site] = $url;
    }
    public function getUrlInput()
    {
        return $this->urlInput;
    }
    public function setUrlInput($urlInput)
    {
        $this->urlInput = $urlInput;
    }
    public function getAsin()
    {
        return $this->asin;
    }
    public function setAsin($asin)
    {
        $this->asin = $asin;
    }
    public function getUpc()
    {
        return $this->upc;
    }
    public function setUpc($upc)
    {
        $this->upc = $upc;
    }
    public function getEan()
    {
        return $this->ean;
    }
    public function setEan($ean)
    {
        $this->ean = $ean;
    }
    public function getUrls()
    {
        return $this->urls;
    }
    public function getTitles()
    {
        return $this->titles;
    }
    public function addTitle($title, $site)
    {
        $this->titles[$site] = $title;
    }
    public function getDescriptions()
    {
        return $this->descriptions;
    }
    public function addDescription($description, $site)
    {
        $this->descriptions[$site] = $description;
    }
    public function getImages()
    {
        return $this->images;
    }
    public function addImage($url, $width, $height, $site)
    {
        $this->images[] = ['url' => $url, 'w' => $width, 'h' => $height, 'site' => $site];
    }
    public function getReviews()
    {
        return $this->reviews;
    }
    public function addReview($review, $site)
    {
        $this->reviews[$site] = $review;
    }
    public function getRatings()
    {
        return $this->ratings;
    }
    public function addRating($stars, $nOfRatings, $site)
    {
        $this->ratings[$site] = ['stars' => $stars, 'number' => $nOfRatings];
    }
    public function getYoutubeVideos()
    {
        return $this->youtubeVideos;
    }
    public function addYoutubeVideo($id, $title, $description)
    {
        $this->youtubeVideos[] = ['id'=>$id, 'title'=>$title, 'description'=>$description];
    }
    public function getPrices()
    {
        return $this->prices;
    }
    public function addPrice($price, $currency, $site)
    {
        if (strlen($currency)==3) {
            $currency = StringUtils::getCurrencySymbol($currency) ?: $currency;
        }
        $this->prices[] = ['amount' => $price, 'currency' => $currency, 'site' => $site];
    }
}
