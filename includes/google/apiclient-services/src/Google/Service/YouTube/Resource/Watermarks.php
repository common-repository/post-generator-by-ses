<?php
class Google_Service_YouTube_Resource_Watermarks extends Google_Service_Resource
{
  public function set($channelId, Google_Service_YouTube_InvideoBranding $postBody, $optParams = array())
  {
    $params = array('channelId' => $channelId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('set', array($params));
  }
  public function unsetWatermarks($channelId, $optParams = array())
  {
    $params = array('channelId' => $channelId);
    $params = array_merge($params, $optParams);
    return $this->call('unset', array($params));
  }
}
