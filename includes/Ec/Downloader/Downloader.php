<?php
namespace Ec\Downloader;
class Downloader
{
    private $connectTimeoutMs = 5000;
    private $timeoutMs = 12000;
    public function downloadRaw($url, $proxy = null, $userAgent = null, $cookieJar = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        $proxy && curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connectTimeoutMs);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->timeoutMs);
        $userAgent && curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        $cookieJar && curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        $cookieJar && curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $html;
    }
    public function download($url, array $options = [])
    {
        $cacheLifeTime = $options['cacheLifetime'] ?? null;
        $useCookie = $options['cookie'] ?? true;
        $cacheFile = $options['cacheFile'] ?? '/tmp/dl_' . md5($url) . '.cache';
        $ts = time() - $cacheLifeTime;
        if ($cacheLifeTime && file_exists($cacheFile) && filemtime($cacheFile) > $ts) {
            if (!empty($options['debug'])) {
                echo "$url found in cache $cacheFile\n";
            }
            return file_get_contents($cacheFile);
        }
        $html = $this->downloadRaw($url);
        $success = strlen($html) > 0 && strpos($html, 'To discuss automated access') === false;
        if ($cacheLifeTime && $html) {
            file_put_contents($cacheFile, $html);
        }
        return $html;
    }
}
