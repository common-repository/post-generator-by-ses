<?php
use GuzzleHttp\Psr7\Request;
class Google_Service_Resource
{
    private $stackParameters = [
      'alt' => ['type' => 'string', 'location' => 'query'],
      'fields' => ['type' => 'string', 'location' => 'query'],
      'trace' => ['type' => 'string', 'location' => 'query'],
      'userIp' => ['type' => 'string', 'location' => 'query'],
      'quotaUser' => ['type' => 'string', 'location' => 'query'],
      'data' => ['type' => 'string', 'location' => 'body'],
      'mimeType' => ['type' => 'string', 'location' => 'header'],
      'uploadType' => ['type' => 'string', 'location' => 'query'],
      'mediaUpload' => ['type' => 'complex', 'location' => 'query'],
      'prettyPrint' => ['type' => 'string', 'location' => 'query'],
  ];
    private $rootUrl;
    private $client;
    private $serviceName;
    private $servicePath;
    private $resourceName;
    private $methods;
    public function __construct($service, $serviceName, $resourceName, $resource)
    {
        $this->rootUrl = $service->rootUrl;
        $this->client = $service->getClient();
        $this->servicePath = $service->servicePath;
        $this->serviceName = $serviceName;
        $this->resourceName = $resourceName;
        $this->methods = is_array($resource) && isset($resource['methods']) ?
        $resource['methods'] :
        [$resourceName => $resource];
    }
    public function call($name, $arguments, $expectedClass = null)
    {
        if (! isset($this->methods[$name])) {
            $this->client->getLogger()->error(
          'Service method unknown',
          [
              'service' => $this->serviceName,
              'resource' => $this->resourceName,
              'method' => $name
          ]
      );
            throw new Google_Exception(
          'Unknown function: ' .
          "{$this->serviceName}->{$this->resourceName}->{$name}()"
      );
        }
        $method = $this->methods[$name];
        $parameters = $arguments[0];
        $postBody = null;
        if (isset($parameters['postBody'])) {
            if ($parameters['postBody'] instanceof Google_Model) {
                $parameters['postBody'] = $parameters['postBody']->toSimpleObject();
            } elseif (is_object($parameters['postBody'])) {
                $parameters['postBody'] =
            $this->convertToArrayAndStripNulls($parameters['postBody']);
            }
            $postBody = (array) $parameters['postBody'];
            unset($parameters['postBody']);
        }
        if (isset($parameters['optParams'])) {
            $optParams = $parameters['optParams'];
            unset($parameters['optParams']);
            $parameters = array_merge($parameters, $optParams);
        }
        if (!isset($method['parameters'])) {
            $method['parameters'] = [];
        }
        $method['parameters'] = array_merge(
        $this->stackParameters,
        $method['parameters']
    );
        foreach ($parameters as $key => $val) {
            if ($key != 'postBody' && ! isset($method['parameters'][$key])) {
                $this->client->getLogger()->error(
            'Service parameter unknown',
            [
                'service' => $this->serviceName,
                'resource' => $this->resourceName,
                'method' => $name,
                'parameter' => $key
            ]
        );
                throw new Google_Exception("($name) unknown parameter: '$key'");
            }
        }
        foreach ($method['parameters'] as $paramName => $paramSpec) {
            if (isset($paramSpec['required']) &&
          $paramSpec['required'] &&
          ! isset($parameters[$paramName])
      ) {
                $this->client->getLogger()->error(
            'Service parameter missing',
            [
                'service' => $this->serviceName,
                'resource' => $this->resourceName,
                'method' => $name,
                'parameter' => $paramName
            ]
        );
                throw new Google_Exception("($name) missing required param: '$paramName'");
            }
            if (isset($parameters[$paramName])) {
                $value = $parameters[$paramName];
                $parameters[$paramName] = $paramSpec;
                $parameters[$paramName]['value'] = $value;
                unset($parameters[$paramName]['required']);
            } else {
                unset($parameters[$paramName]);
            }
        }
        $this->client->getLogger()->info(
        'Service Call',
        [
            'service' => $this->serviceName,
            'resource' => $this->resourceName,
            'method' => $name,
            'arguments' => $parameters,
        ]
    );
        $url = $this->createRequestUri(
        $method['path'],
        $parameters
    );
        $request = new Request(
        $method['httpMethod'],
        $url,
        ['content-type' => 'application/json'],
        $postBody ? json_encode($postBody) : ''
    );
        if (isset($parameters['data'])) {
            $mimeType = isset($parameters['mimeType'])
        ? $parameters['mimeType']['value']
        : 'application/octet-stream';
            $data = $parameters['data']['value'];
            $upload = new Google_Http_MediaFileUpload($this->client, $request, $mimeType, $data);
            $request = $upload->getRequest();
        }
        if (isset($parameters['alt']) && $parameters['alt']['value'] == 'media') {
            $expectedClass = null;
        }
        if ($this->client->shouldDefer()) {
            $request = $request
        ->withHeader('X-Php-Expected-Class', $expectedClass);
            return $request;
        }
        return $this->client->execute($request, $expectedClass);
    }
    protected function convertToArrayAndStripNulls($o)
    {
        $o = (array) $o;
        foreach ($o as $k => $v) {
            if ($v === null) {
                unset($o[$k]);
            } elseif (is_object($v) || is_array($v)) {
                $o[$k] = $this->convertToArrayAndStripNulls($o[$k]);
            }
        }
        return $o;
    }
    public function createRequestUri($restPath, $params)
    {
        if ('/' == substr($restPath, 0, 1)) {
            $requestUrl = substr($restPath, 1);
        } else {
            $requestUrl = $this->servicePath . $restPath;
        }
        if ($this->rootUrl) {
            if ('/' !== substr($this->rootUrl, -1) && '/' !== substr($requestUrl, 0, 1)) {
                $requestUrl = '/' . $requestUrl;
            }
            $requestUrl = $this->rootUrl . $requestUrl;
        }
        $uriTemplateVars = [];
        $queryVars = [];
        foreach ($params as $paramName => $paramSpec) {
            if ($paramSpec['type'] == 'boolean') {
                $paramSpec['value'] = $paramSpec['value'] ? 'true' : 'false';
            }
            if ($paramSpec['location'] == 'path') {
                $uriTemplateVars[$paramName] = $paramSpec['value'];
            } elseif ($paramSpec['location'] == 'query') {
                if (is_array($paramSpec['value'])) {
                    foreach ($paramSpec['value'] as $value) {
                        $queryVars[] = $paramName . '=' . rawurlencode(rawurldecode($value));
                    }
                } else {
                    $queryVars[] = $paramName . '=' . rawurlencode(rawurldecode($paramSpec['value']));
                }
            }
        }
        if (count($uriTemplateVars)) {
            $uriTemplateParser = new Google_Utils_UriTemplate();
            $requestUrl = $uriTemplateParser->parse($requestUrl, $uriTemplateVars);
        }
        if (count($queryVars)) {
            $requestUrl .= '?' . implode($queryVars, '&');
        }
        return $requestUrl;
    }
}
