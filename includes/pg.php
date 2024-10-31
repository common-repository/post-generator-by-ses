<?php
$pluginRoot = realpath(__DIR__ . '/../');
require $pluginRoot . '/includes/autoload.php';
$twigloader = new Twig_Loader_Filesystem($pluginRoot . '/admin/twig-templates/');
$twig = new Twig_Environment($twigloader, [
    'cache' => false,
    'debug' => true,
    'strict_variables' => true
]);
$twig->addExtension(new \Pg\PgTwigExtension());
$downloader = new Ec\Downloader\Downloader();
$cacheDir = WP_CONTENT_DIR . '/cache/post-generator';
$cache = new \Doctrine\Common\Cache\FilesystemCache($cacheDir);
$pf = new Pg\ProductEnricher([
    new Pg\ProductEnricher\AmazonApiEnricher($cache),
            new Pg\ProductEnricher\EbayApiEnricher($downloader, $cache),
        new Pg\ProductEnricher\YoutubeEnricher($cache),
    ]);
return new Pg\PostGenerator(
    $pf,
    $twig,
    glob($pluginRoot . '/admin/twig-templates/*.html.twig')
);
