<?php

// Minimal Yoyo test server for isolated component browser tests.
// Usage: php -S localhost:8765 tests/Browser/server/index.php

require __DIR__.'/../../../vendor/autoload.php';

use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;
use Clickfwd\Yoyo\Yoyo;

$yoyo = new Yoyo();
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$yoyo->configure([
    'url' => '/yoyo',
    'namespace' => 'Tests\\Browser\\Components\\',
    'historyEnabled' => $uri === '/history',
    'htmx' => $uri === '/spinner-swap' ? '/assets/js/htmx.js' : null,
]);

$yoyo->registerViewProvider(function () {
    return new YoyoViewProvider(new View(__DIR__.'/views'));
});

// Yoyo AJAX endpoint
if ($uri === '/yoyo') {
    echo $yoyo->update();
    exit;
}

// Serve yoyo.js from src
if ($uri === '/yoyo.js' || $uri === '/assets/js/yoyo.js') {
    header('Content-Type: application/javascript');
    readfile(__DIR__.'/../../../src/assets/js/yoyo.js');
    exit;
}

$testAssets = [
    '/assets/js/htmx.js' => 'htmx.org/dist/htmx.min.js',
    '/assets/js/morphdom.js' => 'morphdom/dist/morphdom-umd.js',
];

if (isset($testAssets[$uri])) {
    header('Content-Type: application/javascript');
    readfile(__DIR__.'/../../../node_modules/'.$testAssets[$uri]);
    exit;
}

// Component isolation pages
$page = ltrim($uri, '/') ?: 'index';

$pagePath = __DIR__.'/pages/'.$page.'.php';
if (file_exists($pagePath)) {
    ob_start();
    include $pagePath;
    $content = ob_get_clean();
    echo $content;
    exit;
}

http_response_code(404);
echo '404 Not Found';
