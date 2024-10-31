<?php
namespace DTS\eBaySDK\Finding\Services;
class FindingBaseService extends \DTS\eBaySDK\Services\BaseService
{
    const HDR_API_VERSION = 'X-EBAY-SOA-SERVICE-VERSION';
    const HDR_APP_ID = 'X-EBAY-SOA-SECURITY-APPNAME';
    const HDR_GLOBAL_ID = 'X-EBAY-SOA-GLOBAL-ID';
    const HDR_OPERATION_NAME = 'X-EBAY-SOA-OPERATION-NAME';
    public function __construct(array $config)
    {
        parent::__construct('https://svcs.ebay.com/services/search/FindingService/v1', 'https://svcs.sandbox.ebay.com/services/search/FindingService/v1', $config);
    }
    public static function getConfigDefinitions()
    {
        $definitions = parent::getConfigDefinitions();
        return $definitions + [
            'apiVersion' => [
                'valid' => ['string'],
                'default' => \DTS\eBaySDK\Finding\Services\FindingService::API_VERSION
            ],
            'globalId' => [
                'valid' => ['string']
            ]
        ];
    }
    protected function getEbayHeaders($operationName)
    {
        $headers = [];
                $headers[self::HDR_APP_ID] = $this->getConfig('credentials')->getAppId();
        $headers[self::HDR_OPERATION_NAME] = $operationName;
                if ($this->getConfig('apiVersion')) {
            $headers[self::HDR_API_VERSION] = $this->getConfig('apiVersion');
        }
        if ($this->getConfig('globalId')) {
            $headers[self::HDR_GLOBAL_ID] = $this->getConfig('globalId');
        }
        return $headers;
    }
}
