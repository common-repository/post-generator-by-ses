<?php
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
class Google_Http_Batch
{
    const BATCH_PATH = 'batch';
    private static $CONNECTION_ESTABLISHED_HEADERS = [
    "HTTP/1.0 200 Connection established\r\n\r\n",
    "HTTP/1.1 200 Connection established\r\n\r\n",
  ];
    private $boundary;
    private $requests = [];
    private $client;
    private $rootUrl;
    private $batchPath;
    public function __construct(
      Google_Client $client,
      $boundary = false,
      $rootUrl = null,
      $batchPath = null
  ) {
        $this->client = $client;
        $this->boundary = $boundary ?: mt_rand();
        $this->rootUrl = rtrim($rootUrl ?: $this->client->getConfig('base_path'), '/');
        $this->batchPath = $batchPath ?: self::BATCH_PATH;
    }
    public function add(RequestInterface $request, $key = false)
    {
        if (false == $key) {
            $key = mt_rand();
        }
        $this->requests[$key] = $request;
    }
    public function execute()
    {
        $body = '';
        $classes = [];
        $batchHttpTemplate = <<<EOF
--%s
Content-Type: application/http
Content-Transfer-Encoding: binary
MIME-Version: 1.0
Content-ID: %s
%s
%s%s
EOF;
        foreach ($this->requests as $key => $request) {
            $firstLine = sprintf(
          '%s %s HTTP/%s',
          $request->getMethod(),
          $request->getRequestTarget(),
          $request->getProtocolVersion()
      );
            $content = (string) $request->getBody();
            $headers = '';
            foreach ($request->getHeaders() as $name => $values) {
                $headers .= sprintf("%s:%s\r\n", $name, implode(', ', $values));
            }
            $body .= sprintf(
          $batchHttpTemplate,
          $this->boundary,
          $key,
          $firstLine,
          $headers,
          $content ? "\n" . $content : ''
      );
            $classes['response-' . $key] = $request->getHeaderLine('X-Php-Expected-Class');
        }
        $body .= "--{$this->boundary}--";
        $body = trim($body);
        $url = $this->rootUrl . '/' . $this->batchPath;
        $headers = [
      'Content-Type' => sprintf('multipart/mixed; boundary=%s', $this->boundary),
      'Content-Length' => strlen($body),
    ];
        $request = new Request(
        'POST',
        $url,
        $headers,
        $body
    );
        $response = $this->client->execute($request);
        return $this->parseResponse($response, $classes);
    }
    public function parseResponse(ResponseInterface $response, $classes = [])
    {
        $contentType = $response->getHeaderLine('content-type');
        $contentType = explode(';', $contentType);
        $boundary = false;
        foreach ($contentType as $part) {
            $part = explode('=', $part, 2);
            if (isset($part[0]) && 'boundary' == trim($part[0])) {
                $boundary = $part[1];
            }
        }
        $body = (string) $response->getBody();
        if (!empty($body)) {
            $body = str_replace("--$boundary--", "--$boundary", $body);
            $parts = explode("--$boundary", $body);
            $responses = [];
            $requests = array_values($this->requests);
            foreach ($parts as $i => $part) {
                $part = trim($part);
                if (!empty($part)) {
                    list($rawHeaders, $part) = explode("\r\n\r\n", $part, 2);
                    $headers = $this->parseRawHeaders($rawHeaders);
                    $status = substr($part, 0, strpos($part, "\n"));
                    $status = explode(' ', $status);
                    $status = $status[1];
                    list($partHeaders, $partBody) = $this->parseHttpResponse($part, false);
                    $response = new Response(
              $status,
              $partHeaders,
              Psr7\stream_for($partBody)
          );
                    $key = $headers['content-id'];
                    try {
                        $response = Google_Http_REST::decodeHttpResponse($response, $requests[$i-1]);
                    } catch (Google_Service_Exception $e) {
                        $response = $e;
                    }
                    $responses[$key] = $response;
                }
            }
            return $responses;
        }
        return null;
    }
    private function parseRawHeaders($rawHeaders)
    {
        $headers = [];
        $responseHeaderLines = explode("\r\n", $rawHeaders);
        foreach ($responseHeaderLines as $headerLine) {
            if ($headerLine && strpos($headerLine, ':') !== false) {
                list($header, $value) = explode(': ', $headerLine, 2);
                $header = strtolower($header);
                if (isset($headers[$header])) {
                    $headers[$header] .= "\n" . $value;
                } else {
                    $headers[$header] = $value;
                }
            }
        }
        return $headers;
    }
    private function parseHttpResponse($respData, $headerSize)
    {
        foreach (self::$CONNECTION_ESTABLISHED_HEADERS as $established_header) {
            if (stripos($respData, $established_header) !== false) {
                $respData = str_ireplace($established_header, '', $respData);
                break;
            }
        }
        if ($headerSize) {
            $responseBody = substr($respData, $headerSize);
            $responseHeaders = substr($respData, 0, $headerSize);
        } else {
            $responseSegments = explode("\r\n\r\n", $respData, 2);
            $responseHeaders = $responseSegments[0];
            $responseBody = isset($responseSegments[1]) ? $responseSegments[1] :
                                                    null;
        }
        $responseHeaders = $this->parseRawHeaders($responseHeaders);
        return [$responseHeaders, $responseBody];
    }
}
