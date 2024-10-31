<?php
class Google_Service_YouTube_Resource_ChannelBanners extends Google_Service_Resource
{
  public function insert(Google_Service_YouTube_ChannelBannerResource $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_ChannelBannerResource");
  }
}
