<?php
namespace Zend\Http\Client\Adapter;
interface AdapterInterface
{
    public function setOptions($options = []);
    public function connect($host, $port = 80, $secure = false);
    public function write($method, $url, $httpVer = '1.1', $headers = [], $body = '');
    public function read();
    public function close();
}
