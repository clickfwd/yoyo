<?php

use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;
use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;
use Clickfwd\Yoyo\Yoyo;
use function Tests\update;

beforeAll(function () {
    $yoyo = new Yoyo();

    $yoyo->configure([
      'namespace' => 'Tests\\App\\Yoyo\\',
    ]);

    require_once __DIR__.'/../app/Yoyo/ProtectedMethods.php';

    $view = new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo'));

    $yoyo->setViewProvider($view);
});

test('component method is not public', function () {
    update('protected-methods', 'secret');
})->throws(NonPublicComponentMethodCall::class)->group('notpublic');
