<?php
require __DIR__ . '/Burgomaster.php';
$stageDirectory = __DIR__ . '/artifacts/staging';
$projectRoot = __DIR__ . '/../';
$packager = new \Burgomaster($stageDirectory, $projectRoot);
foreach (['README.md', 'LICENSE'] as $file) {
    $packager->deepCopy($file, $file);
}
$packager->recursiveCopy('src', 'GuzzleHttp', ['php']);
$packager->recursiveCopy('vendor/guzzlehttp/promises/src', 'GuzzleHttp/Promise');
$packager->recursiveCopy('vendor/guzzlehttp/psr7/src', 'GuzzleHttp/Psr7');
$packager->recursiveCopy('vendor/psr/http-message/src', 'Psr/Http/Message');
$packager->createAutoloader([
    'GuzzleHttp/functions_include.php',
    'GuzzleHttp/Psr7/functions_include.php',
    'GuzzleHttp/Promise/functions_include.php',
]);
$packager->createPhar(__DIR__ . '/artifacts/guzzle.phar');
$packager->createZip(__DIR__ . '/artifacts/guzzle.zip');
