<?php
class Google_Service_YouTube_Resource_I18nLanguages extends Google_Service_Resource
{
  public function listI18nLanguages($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_I18nLanguageListResponse");
  }
}
