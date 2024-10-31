<?php
namespace DTS\eBaySDK\Credentials;
interface CredentialsInterface
{
    public function getAppId();
    public function getCertId();
    public function getDevId();
}
