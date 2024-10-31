<?php
class Google_Service_YouTube_Resource_Activities extends Google_Service_Resource
{
  public function insert($part, Google_Service_YouTube_Activity $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_Activity");
  }
  public function listActivities($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_ActivityListResponse");
  }
}
