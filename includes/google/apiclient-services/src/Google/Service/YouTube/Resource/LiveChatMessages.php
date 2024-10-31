<?php
class Google_Service_YouTube_Resource_LiveChatMessages extends Google_Service_Resource
{
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }
  public function insert($part, Google_Service_YouTube_LiveChatMessage $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_LiveChatMessage");
  }
  public function listLiveChatMessages($liveChatId, $part, $optParams = array())
  {
    $params = array('liveChatId' => $liveChatId, 'part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_LiveChatMessageListResponse");
  }
}
