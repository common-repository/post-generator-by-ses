<?php
namespace ZendService\Amazon\Authentication;
use Zend\Crypt\Hmac;
class V1 extends AbstractAuthentication
{
    protected $_signatureVersion = '1';
    protected $_signatureMethod = 'HmacSHA256';
    public function generateSignature($url, array &$parameters)
    {
        $parameters['AWSAccessKeyId']   = $this->_accessKey;
        $parameters['SignatureVersion'] = $this->_signatureVersion;
        $parameters['Version']          = $this->_apiVersion;
        if (! isset($parameters['Timestamp'])) {
            $parameters['Timestamp']    = gmdate('Y-m-d\TH:i:s\Z', time() + 10);
        }
        $data = $this->_signParameters($url, $parameters);
        return $data;
    }
    protected function _signParameters($url, array &$parameters)
    {
        $data = '';
        uksort($parameters, 'strcasecmp');
        unset($parameters['Signature']);
        foreach ($parameters as $key => $value) {
            $data .= $key . $value;
        }
        $hmac = Hmac::compute($this->_secretKey, 'SHA1', $data, Hmac::OUTPUT_BINARY);
        $parameters['Signature'] = base64_encode($hmac);
        return $data;
    }
}
