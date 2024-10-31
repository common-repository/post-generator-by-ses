<?php
namespace ZendService\Amazon;
use DOMDocument;
use DOMXPath;
use Zend\Crypt\Hmac;
use ZendRest\Client\RestClient;
class Amazon
{
    public $appId;
    protected static $version = '2011-08-01';
    protected $_secretKey = null;
    protected $_baseUri = null;
    protected $_baseUriList = [
        'BR' => 'http://webservices.amazon.com.br',
        'CA' => 'http://webservices.amazon.ca',
        'CN' => 'http://webservices.amazon.cn',
        'DE' => 'http://webservices.amazon.de',
        'ES' => 'http://webservices.amazon.es',
        'FR' => 'http://webservices.amazon.fr',
        'JP' => 'http://webservices.amazon.co.jp',
        'IN' => 'http://webservices.amazon.in',
        'IT' => 'http://webservices.amazon.it',
        'MX' => 'http://webservices.amazon.com.mx',
        'UK' => 'http://webservices.amazon.co.uk',
        'US' => 'http://webservices.amazon.com',
    ];
    protected $_rest = null;
    protected $_lastResponse = null;
    public function __construct(
        $appId,
        $countryCode = 'US',
        $secretKey = null,
        $version = null,
        $useHttps = false
    ) {
        $this->appId = (string) $appId;
        $this->_secretKey = $secretKey;
        if (! is_null($version)) {
            self::setVersion($version);
        }
        $countryCode = (string) $countryCode;
        if (! isset($this->_baseUriList[$countryCode])) {
            throw new Exception\InvalidArgumentException("Unknown country code: $countryCode");
        }
        $this->_baseUri = $useHttps
            ? str_replace('http:', 'https:', $this->_baseUriList[$countryCode])
            : $this->_baseUriList[$countryCode];
    }
    public function itemSearch(array $options)
    {
        $client = $this->getRestClient();
        $client->setUri($this->_baseUri);
        $defaultOptions = ['ResponseGroup' => 'Small'];
        $options = $this->_prepareOptions('ItemSearch', $options, $defaultOptions);
        $client->getHttpClient()->resetParameters();
        $response = $client->restGet('/onca/xml', $options);
        $this->_lastResponse = $response;
        if ($response->isClientError()) {
            throw new Exception\RuntimeException('An error occurred sending request. Status code: '
                                           . $response->getStatusCode() . '. Body:' . $response->getBody());
        }
        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());
        self::_checkErrors($dom);
        return new ResultSet($dom);
    }
    public function itemLookup($asin, array $options = [])
    {
        $client = $this->getRestClient();
        $client->setUri($this->_baseUri);
        $client->getHttpClient()->resetParameters();
        $defaultOptions = ['ResponseGroup' => 'Small'];
        $options['ItemId'] = (string) $asin;
        $options = $this->_prepareOptions('ItemLookup', $options, $defaultOptions);
        $response = $client->restGet('/onca/xml', $options);
        $this->_lastResponse = $response;
        if ($response->isClientError()) {
            throw new Exception\RuntimeException(
                'An error occurred sending request. Status code: '
                . $response->getStatusCode()
                . $response->getBody()
            );
        }
        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());
        self::_checkErrors($dom);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . self::getVersion());
        $items = $xpath->query('//az:Items/az:Item');
        if ($items->length == 1) {
            return new Item($items->item(0));
        }
        return new ResultSet($dom);
    }
    public function browseNodeLookup(array $options)
    {
        $client = $this->getRestClient();
        $client->setUri($this->_baseUri);
        $defaultOptions = ['ResponseGroup' => 'BrowseNodeInfo'];
        $options = $this->_prepareOptions('BrowseNodeLookup', $options, $defaultOptions);
        $client->getHttpClient()->resetParameters();
        $response = $client->restGet('/onca/xml', $options);
        $this->_lastResponse = $response;
        if ($response->isClientError()) {
            throw new Exception\RuntimeException('An error occurred sending request. Status code: '
                . $response->getStatusCode() . ' Body:' . $response->getBody());
        }
        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());
        self::_checkErrors($dom);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . self::getVersion());
        $items = $xpath->query('//az:BrowseNodes/az:BrowseNode');
        if ($items->length == 1) {
            return new BrowseNode($items->item(0));
        }
        throw new Exception\RuntimeException('cannot find BrowseNodes/BrowseNode. Body:' . $response->getBody());
    }
    public function getRestClient()
    {
        if ($this->_rest === null) {
            $this->_rest = new RestClient();
        }
        return $this->_rest;
    }
    public function setRestClient(RestClient $client)
    {
        $this->_rest = $client;
        return $this;
    }
    protected function _prepareOptions($query, array $options, array $defaultOptions)
    {
        $options['AWSAccessKeyId'] = $this->appId;
        $options['Service']        = 'AWSECommerceService';
        $options['Operation']      = (string) $query;
        $options['Version']        = self::getVersion();
        if (isset($options['ResponseGroup'])) {
            $responseGroup = explode(',', $options['ResponseGroup']);
            if (! in_array('Request', $responseGroup)) {
                $responseGroup[] = 'Request';
                $options['ResponseGroup'] = implode(',', $responseGroup);
            }
        }
        $options = array_merge($defaultOptions, $options);
        if ($this->_secretKey !== null) {
            $options['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
            ksort($options);
            $options['Signature'] = self::computeSignature($this->_baseUri, $this->_secretKey, $options);
        }
        return $options;
    }
    public static function computeSignature($baseUri, $secretKey, array $options)
    {
        $signature = self::buildRawSignature($baseUri, $options);
        return base64_encode(
            Hmac::compute($secretKey, 'sha256', $signature, Hmac::OUTPUT_BINARY)
        );
    }
    public static function buildRawSignature($baseUri, $options)
    {
        ksort($options);
        $params = [];
        foreach ($options as $k => $v) {
            $params[] = $k . '=' . rawurlencode($v);
        }
        return sprintf(
            "GET\n%s\n/onca/xml\n%s",
            str_replace('http://', '', $baseUri),
            implode('&', $params)
        );
    }
    protected static function _checkErrors(DOMDocument $dom)
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/' . self::getVersion());
        if ($xpath->query('//az:Error')->length >= 1) {
            $code = $xpath->query('//az:Error/az:Code/text()')->item(0)->data;
            $message = $xpath->query('//az:Error/az:Message/text()')->item(0)->data;
            if (
                $code == 'AWS.ECommerceService.ItemNotAccessible' ||
                ($code == 'AWS.InvalidParameterValue' && strpos($message, 'not a valid value for ItemId')!==false)
            ) {
                throw new Exception\InvalidItemIdException($message);
            }
            throw new Exception\RuntimeException("$message ($code)");
        }
        if ($dom->getElementsByTagName('Error')->length > 0) {
            $errorNode = $dom->getElementsByTagName('Error')->item(0);
            $code = $errorNode->getElementsByTagName('Code')->item(0)->textContent;
            $message = $errorNode->getElementsByTagName('Message')->item(0)->textContent;
            if ($code == 'RequestThrottled' && strpos($message, 'You are submitting requests too quickly') !== false) {
                throw new Exception\TooQuicklyException($message);
            }
        }
    }
    public static function setVersion($version)
    {
        if (! preg_match('/\d{4}-\d{2}-\d{2}/', $version)) {
            throw new Exception\InvalidArgumentException("$version is an invalid API Version");
        }
        self::$version = $version;
    }
    public static function getVersion()
    {
        return self::$version;
    }
    public function getLastResponse()
    {
        return $this->_lastResponse;
    }
}
