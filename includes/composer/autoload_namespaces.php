<?php
$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);
return [
    'ZendRest' => [$vendorDir . '/zendframework/zendrest/library'],
    'Twig_' => [$vendorDir . '/twig/twig/lib'],
    'Google_Service_' => [$vendorDir . '/google/apiclient-services/src'],
    'Google_' => [$vendorDir . '/google/apiclient/src'],
];
