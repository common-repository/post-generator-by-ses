<?php
namespace Ec\Ebay;
use DTS\eBaySDK\Finding\Enums;
use DTS\eBaySDK\Finding\Services\FindingService;
use DTS\eBaySDK\Finding\Types;
use GuzzleHttp\Exception\RequestException;
class EbayService
{
    private $service;
    public function __construct(array $ebayConfig)
    {
        if (empty($ebayConfig['globalId'])) {
            throw new \RuntimeException('globalId missing');
        }
        $this->service =  new FindingService(
            ['apiVersion'  => '1.13.0'] + $ebayConfig
        );
        ;
    }
    private static function createItemFilter($name, array $values)
    {
        $itemFilter = new Types\ItemFilter();
        $itemFilter->name = $name;
        foreach ($values as $v) {
            $itemFilter->value[] = $v;
        }
        return $itemFilter;
    }
    public static function roveriseLink($link, $campaignId, $roverId)
    {
        $toolId = 20004;
        return "https://rover.ebay.com/rover/1/{$roverId}/1?icep_id=114&ipn=icep&toolid={$toolId}&campid={$campaignId}&mpre=" . urlencode($link);
    }
    public function findBy(array $options)
    {
        if (empty($options['keywords'])) {
            throw new \InvalidArgumentException(__METHOD__ . ': keywords missing');
        }
        $request = new Types\FindItemsAdvancedRequest();
        $request->keywords = $options['keywords'];
        $request->itemFilter[] = self::createItemFilter('ListingType', ['FixedPrice']);
        $request->itemFilter[] = self::createItemFilter('Condition', [$options['condition'] ?: 'New']);
        $request->itemFilter[] = self::createItemFilter('MinPrice', [(string) ($options['minPrice'] ?: 1)]);
        $request->itemFilter[] = self::createItemFilter('MaxPrice', [(string) ($options['maxPrice'] ?: 99999.99)]);
        $request->sortOrder = 'BestMatch';
        $request->paginationInput = new Types\PaginationInput();
        $request->paginationInput->entriesPerPage = (int) ($options['limit'] ?: 1);
        $request->paginationInput->pageNumber = 1;
        try {
            $response = $this->service->findItemsAdvanced($request);
        } catch (RequestException $re) {
            if (strpos($re->getResponse()->getBody(), 'Service call has exceeded') !== null) {
                throw new \RuntimeException('HitApiLimit');
            }
            throw $re;
        }
        if (isset($response->errorMessage)) {
            foreach ($response->errorMessage->error as $error) {
                if ($error->severity === Enums\ErrorSeverity::C_ERROR) {
                    throw new \RuntimeException($error->message);
                } else {
                    trigger_error($error->message, E_USER_NOTICE);
                }
            }
        }
        if ($response->ack === 'Failure') {
            throw new \RuntimeException('ack failure');
        }
        return $response->searchResult->item;
    }
}
