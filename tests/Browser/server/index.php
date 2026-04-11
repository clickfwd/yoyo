<?php

// Minimal Yoyo test server for isolated component browser tests.
// Usage: php -S localhost:8765 tests/Browser/server/index.php

require __DIR__.'/../../../vendor/autoload.php';

use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;
use Clickfwd\Yoyo\Yoyo;

$yoyo = new Yoyo();

$yoyo->configure([
    'url' => '/yoyo',
    'namespace' => 'Tests\\Browser\\Components\\',
]);

$yoyo->registerViewProvider(function () {
    return new YoyoViewProvider(new View(__DIR__.'/views'));
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

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
