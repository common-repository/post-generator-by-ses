<?php
namespace DTS\eBaySDK\Finding\Types;
class SearchItem extends \DTS\eBaySDK\Types\BaseType
{
    private static $propertyTypes = [
        'itemId' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'itemId'
        ],
        'title' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'title'
        ],
        'globalId' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'globalId'
        ],
        'subtitle' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'subtitle'
        ],
        'primaryCategory' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Category',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'primaryCategory'
        ],
        'secondaryCategory' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Category',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'secondaryCategory'
        ],
        'galleryURL' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'galleryURL'
        ],
        'galleryInfoContainer' => [
            'type' => 'DTS\eBaySDK\Finding\Types\GalleryInfoContainer',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'galleryInfoContainer'
        ],
        'viewItemURL' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'viewItemURL'
        ],
        'charityId' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'charityId'
        ],
        'productId' => [
            'type' => 'DTS\eBaySDK\Finding\Types\ProductId',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'productId'
        ],
        'paymentMethod' => [
            'type' => 'string',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'paymentMethod'
        ],
        'autoPay' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'autoPay'
        ],
        'postalCode' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'postalCode'
        ],
        'location' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'location'
        ],
        'country' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'country'
        ],
        'storeInfo' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Storefront',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'storeInfo'
        ],
        'sellerInfo' => [
            'type' => 'DTS\eBaySDK\Finding\Types\SellerInfo',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'sellerInfo'
        ],
        'shippingInfo' => [
            'type' => 'DTS\eBaySDK\Finding\Types\ShippingInfo',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'shippingInfo'
        ],
        'sellingStatus' => [
            'type' => 'DTS\eBaySDK\Finding\Types\SellingStatus',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'sellingStatus'
        ],
        'listingInfo' => [
            'type' => 'DTS\eBaySDK\Finding\Types\ListingInfo',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'listingInfo'
        ],
        'returnsAccepted' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'returnsAccepted'
        ],
        'galleryPlusPictureURL' => [
            'type' => 'string',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'galleryPlusPictureURL'
        ],
        'compatibility' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'compatibility'
        ],
        'distance' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Distance',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'distance'
        ],
        'condition' => [
            'type' => 'DTS\eBaySDK\Finding\Types\Condition',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'condition'
        ],
        'isMultiVariationListing' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'isMultiVariationListing'
        ],
        'discountPriceInfo' => [
            'type' => 'DTS\eBaySDK\Finding\Types\DiscountPriceInfo',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'discountPriceInfo'
        ],
        'pictureURLSuperSize' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'pictureURLSuperSize'
        ],
        'pictureURLLarge' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'pictureURLLarge'
        ],
        'unitPrice' => [
            'type' => 'DTS\eBaySDK\Finding\Types\UnitPriceInfo',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'unitPrice'
        ],
        'attribute' => [
            'type' => 'DTS\eBaySDK\Finding\Types\ItemAttribute',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'attribute'
        ],
        'topRatedListing' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'topRatedListing'
        ],
        'eekStatus' => [
            'type' => 'string',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'eekStatus'
        ]
    ];
    public function __construct(array $values = [])
    {
        list($parentValues, $childValues) = self::getParentValues(self::$propertyTypes, $values);
        parent::__construct($parentValues);
        if (!array_key_exists(__CLASS__, self::$properties)) {
            self::$properties[__CLASS__] = array_merge(self::$properties[get_parent_class()], self::$propertyTypes);
        }
        if (!array_key_exists(__CLASS__, self::$xmlNamespaces)) {
            self::$xmlNamespaces[__CLASS__] = 'xmlns="http://www.ebay.com/marketplace/search/v1/services"';
        }
        $this->setValues(__CLASS__, $childValues);
    }
}
