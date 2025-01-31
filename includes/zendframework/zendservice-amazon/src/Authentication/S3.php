<?php
namespace ZendService\Amazon\Authentication;
use Zend\Crypt\Hmac;
class S3 extends AbstractAuthentication
{
    public function generateSignature($method, $path, &$headers)
    {
        if (! is_array($headers)) {
            $headers = [$headers];
        }
        $type = $md5 = $date = '';
        foreach ($headers as $key => $val) {
            if (strcasecmp($key, 'content-type') == 0) {
                $type = $val;
            } elseif (strcasecmp($key, 'content-md5') == 0) {
                $md5 = $val;
            } elseif (strcasecmp($key, 'date') == 0) {
                $date = $val;
            }
        }
        if (isset($headers['x-amz-date']) && isset($date)) {
            $date = '';
        }
        $sig_str = "$method\n$md5\n$type\n$date\n";
        $amz_headers = [];
        foreach ($headers as $key => $val) {
            $key = strtolower($key);
            if (substr($key, 0, 6) == 'x-amz-') {
                if (is_array($val)) {
                    $amz_headers[$key] = $val;
                } else {
                    $amz_headers[$key][] = preg_replace('/\s+/', ' ', $val);
                }
            }
        }
        if (! empty($amz_headers)) {
            ksort($amz_headers);
            foreach ($amz_headers as $key => $val) {
                $sig_str .= $key . ':' . implode(',', $val) . "\n";
            }
        }
        $sig_str .= '/' . parse_url($path, PHP_URL_PATH);
        if (strpos($path, '?location') !== false) {
            $sig_str .= '?location';
        } elseif (strpos($path, '?acl') !== false) {
            $sig_str .= '?acl';
        } elseif (strpos($path, '?torrent') !== false) {
            $sig_str .= '?torrent';
        }
        $signature = Hmac::compute($this->_secretKey, 'sha1', utf8_encode($sig_str), Hmac::OUTPUT_BINARY);
        $headers['Authorization'] = 'AWS ' . $this->_accessKey . ':' . base64_encode($signature);
        return $sig_str;
    }
}
