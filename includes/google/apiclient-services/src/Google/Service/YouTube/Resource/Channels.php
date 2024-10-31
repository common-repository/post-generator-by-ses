<?php
class Google_Service_YouTube_Resource_Channels extends Google_Service_Resource
{
  public function listChannels($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_ChannelListResponse");
  }
  public function update($part, Google_Service_YouTube_Channel $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_Channel");
  }
}
