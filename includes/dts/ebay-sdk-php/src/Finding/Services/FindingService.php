<?php
namespace DTS\eBaySDK\Finding\Services;
class FindingService extends \DTS\eBaySDK\Finding\Services\FindingBaseService
{
    const API_VERSION = '1.13.0';
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }
    public function getSearchKeywordsRecommendation(\DTS\eBaySDK\Finding\Types\GetSearchKeywordsRecommendationRequest $request)
    {
        return $this->getSearchKeywordsRecommendationAsync($request)->wait();
    }
    public function getSearchKeywordsRecommendationAsync(\DTS\eBaySDK\Finding\Types\GetSearchKeywordsRecommendationRequest $request)
    {
        return $this->callOperationAsync(
            'getSearchKeywordsRecommendation',
            $request,
            '\DTS\eBaySDK\Finding\Types\GetSearchKeywordsRecommendationResponse'
        );
    }
    public function findItemsByKeywords(\DTS\eBaySDK\Finding\Types\FindItemsByKeywordsRequest $request)
    {
        return $this->findItemsByKeywordsAsync($request)->wait();
    }
    public function findItemsByKeywordsAsync(\DTS\eBaySDK\Finding\Types\FindItemsByKeywordsRequest $request)
    {
        return $this->callOperationAsync(
            'findItemsByKeywords',
            $request,
            '\DTS\eBaySDK\Finding\Types\FindItemsByKeywordsResponse'
        );
    }
    public function findItemsByCategory(\DTS\eBaySDK\Finding\Types\FindItemsByCategoryRequest $request)
    {
        return $this->findItemsByCategoryAsync($request)->wait();
    }
    public function findItemsByCategoryAsync(\DTS\eBaySDK\Finding\Types\FindItemsByCategoryRequest $request)
    {
        return $this->callOperationAsync(
            'findItemsByCategory',
            $request,
            '\DTS\eBaySDK\Finding\Types\FindItemsByCategoryResponse'
        );
    }
    public function findItemsAdvanced(\DTS\eBaySDK\Finding\Types\FindItemsAdvancedRequest $request)
    {
        return $this->findItemsAdvancedAsync($request)->wait();
    }
    public function findItemsAdvancedAsync(\DTS\eBaySDK\Finding\Types\FindItemsAdvancedRequest $request)
    {
        return $this->callOperationAsync(
            'findItemsAdvanced',
            $request,
            '\DTS\eBaySDK\Finding\Types\FindItemsAdvancedResponse'
        );
    }
    public function findItemsByProduct(\DTS\eBaySDK\Finding\Types\FindItemsByProductRequest $request)
    {
        return $this->findItemsByProductAsync($request)->wait();
    }
    public function findItemsByProductAsync(\DTS\eBaySDK\Finding\Types\FindItemsByProductRequest $request)
    {
        return $this->callOperationAsync(
            'findItemsByProduct',
            $request,
            '\DTS\eBaySDK\Finding\Types\FindItemsByProductResponse'
        );
    }
    public function findItemsIneBayStores(\DTS\eBaySDK\Finding\Types\FindItemsIneBayStoresRequest $request)
    {
        return $this->findItemsIneBayStoresAsync($request)->wait();
    }
    public function findItemsIneBayStoresAsync(\DTS\eBaySDK\Finding\Types\FindItemsIneBayStoresRequest $request)
    {
        return $this->callOperationAsync(
            'findItemsIneBayStores',
            $request,
            '\DTS\eBaySDK\Finding\Types\FindItemsIneBayStoresResponse'
        );
    }
    public function findItemsByImage(\DTS\eBaySDK\Finding\Types\FindItemsByImageRequest $request)
    {
        return $this->findItemsByImageAsync($request)->wait();
    }
    public function findItemsByImageAsync(\DTS\eBaySDK\Finding\Types\FindItemsByImageRequest $request)
    {
        return $this->callOperationAsync(
            'findItemsByImage',
            $request,
            '\DTS\eBaySDK\Finding\Types\FindItemsByImageResponse'
        );
    }
    public function getHistograms(\DTS\eBaySDK\Finding\Types\GetHistogramsRequest $request)
    {
        return $this->getHistogramsAsync($request)->wait();
    }
    public function getHistogramsAsync(\DTS\eBaySDK\Finding\Types\GetHistogramsRequest $request)
    {
        return $this->callOperationAsync(
            'getHistograms',
            $request,
            '\DTS\eBaySDK\Finding\Types\GetHistogramsResponse'
        );
    }
    public function getVersion(\DTS\eBaySDK\Finding\Types\GetVersionRequest $request)
    {
        return $this->getVersionAsync($request)->wait();
    }
    public function getVersionAsync(\DTS\eBaySDK\Finding\Types\GetVersionRequest $request)
    {
        return $this->callOperationAsync(
            'getVersion',
            $request,
            '\DTS\eBaySDK\Finding\Types\GetVersionResponse'
        );
    }
    public function findItemsForFavoriteSearch(\DTS\eBaySDK\Finding\Types\FindItemsForFavoriteSearchRequest $request)
    {
        return $this->findItemsForFavoriteSearchAsync($request)->wait();
    }
    public function findItemsForFavoriteSearchAsync(\DTS\eBaySDK\Finding\Types\FindItemsForFavoriteSearchRequest $request)
    {
        return $this->callOperationAsync(
            'findItemsForFavoriteSearch',
            $request,
            '\DTS\eBaySDK\Finding\Types\FindItemsForFavoriteSearchResponse'
        );
    }
    public function findCompletedItems(\DTS\eBaySDK\Finding\Types\FindCompletedItemsRequest $request)
    {
        return $this->findCompletedItemsAsync($request)->wait();
    }
    public function findCompletedItemsAsync(\DTS\eBaySDK\Finding\Types\FindCompletedItemsRequest $request)
    {
        return $this->callOperationAsync(
            'findCompletedItems',
            $request,
            '\DTS\eBaySDK\Finding\Types\FindCompletedItemsResponse'
        );
    }
}
