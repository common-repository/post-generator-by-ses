<?php
namespace DTS\eBaySDK\Credentials;
class Credentials implements \DTS\eBaySDK\Credentials\CredentialsInterface
{
    private $appId;
    private $certId;
    private $devId;
    public function __construct($appId, $certId, $devId)
    {
        $this->appId = trim($appId);
        $this->certId = trim($certId);
        $this->devId = trim($devId);
    }
    public function getAppId()
    {
        return $this->appId;
    }
    public function getCertId()
    {
        return $this->certId;
    }
    public function getDevId()
    {
        return $this->devId;
    }
}
