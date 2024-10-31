<?php
class Google_Service_YouTube_Resource_Thumbnails extends Google_Service_Resource
{
  public function set($videoId, $optParams = array())
  {
    $params = array('videoId' => $videoId);
    $params = array_merge($params, $optParams);
    return $this->call('set', array($params), "Google_Service_YouTube_ThumbnailSetResponse");
  }
}
