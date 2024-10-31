<?php
namespace ZendService\Amazon;
use DateTime;
use Zend\Http\Client as HttpClient;
abstract class AbstractAmazon
{
    protected $secretKey;
    protected $accessKey;
    protected $httpClient = null;
    protected $requestDate = null;
    protected $lastResponse = null;
    const DATE_PRESERVE_KEY = 'preserve';
    const AMAZON_DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';
    public function __construct($accessKey = null, $secretKey = null, HttpClient $httpClient = null)
    {
        $this->setKeys($accessKey, $secretKey);
        $this->setHttpClient(($httpClient) ?: new HttpClient());
    }
    public function setKeys($accessKey, $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        return $this;
    }
    public function getHttpClient()
    {
        return $this->httpClient;
    }
    public function setRequestDate(DateTime $date = null, $preserve = null)
    {
        if ($date instanceof DateTime && ! is_null($preserve)) {
            $date->{self::DATE_PRESERVE_KEY} = (boolean) $preserve;
        }
        $this->requestDate = $date;
    }
    protected function _getAccessKey()
    {
        if (is_null($this->accessKey)) {
            throw new Exception\InvalidArgumentException('AWS access key was not supplied');
        }
        return $this->accessKey;
    }
    protected function _getSecretKey()
    {
        if (is_null($this->secretKey)) {
            throw new Exception\InvalidArgumentException('AWS secret key was not supplied');
        }
        return $this->secretKey;
    }
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
    public function getRequestDate()
    {
        if (! is_object($this->requestDate)) {
            $date = new DateTime();
        } else {
            $date = $this->requestDate;
            if (empty($date->{self::DATE_PRESERVE_KEY})) {
                $this->requestDate = null;
            }
        }
        $date->setTimezone(new \DateTimeZone('GMT'));
        return $date->format(self::AMAZON_DATE_FORMAT);
    }
    public function getRequestIsoDate()
    {
        if (! is_object($this->requestDate)) {
            $date = new DateTime();
        } else {
            $date = $this->requestDate;
        }
        return $date->format('Y-m-d\TH:i:s.000\Z');
    }
}
