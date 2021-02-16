<?php

use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;
use Clickfwd\Yoyo\Yoyo;

$yoyo = new Yoyo();

$yoyo->configure([
    'namespace' => 'Tests\\App\\Yoyo\\',
]);

$yoyo->registerViewProvider(function () {
    return new YoyoViewProvider(new View(__DIR__.'/app/resources/views/yoyo'));
});
