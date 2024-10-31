<?php
class Google_Service_YouTube_Resource_GuideCategories extends Google_Service_Resource
{
  public function listGuideCategories($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_GuideCategoryListResponse");
  }
}
