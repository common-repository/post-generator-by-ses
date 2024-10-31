<?php
namespace ZendService\Amazon\Authentication;
use Zend\Crypt\Hmac;
class V2 extends AbstractAuthentication
{
    protected $_signatureVersion = '2';
    protected $_signatureMethod = 'HmacSHA256';
    protected $_httpMethod = 'POST';
    public function generateSignature($url, array &$parameters)
    {
        $parameters['AWSAccessKeyId']   = $this->_accessKey;
        $parameters['SignatureVersion'] = $this->_signatureVersion;
        $parameters['Version']          = $this->_apiVersion;
        $parameters['SignatureMethod']  = $this->_signatureMethod;
        if (! isset($parameters['Timestamp'])) {
            $parameters['Timestamp']    = gmdate('Y-m-d\TH:i:s\Z', time() + 10);
        }
        $data = $this->_signParameters($url, $parameters);
        return $data;
    }
    public function setHttpMethod($method = 'POST')
    {
        $this->_httpMethod = strtoupper($method);
    }
    public function getHttpMethod()
    {
        return $this->_httpMethod;
    }
    protected function _signParameters($url, array &$parameters)
    {
        $data = $this->_httpMethod . "\n";
        $data .= parse_url($url, PHP_URL_HOST) . "\n";
        $data .= ('' == $path = parse_url($url, PHP_URL_PATH)) ? '/' : $path;
        $data .= "\n";
        uksort($parameters, 'strcmp');
        unset($parameters['Signature']);
        $arrData = [];
        foreach ($parameters as $key => $value) {
            $arrData[] = $key . '=' . str_replace('%7E', '~', rawurlencode($value));
        }
        $data .= implode('&', $arrData);
        $hmac = Hmac::compute($this->_secretKey, 'SHA256', $data, Hmac::OUTPUT_BINARY);
        $parameters['Signature'] = base64_encode($hmac);
        return $data;
    }
}
