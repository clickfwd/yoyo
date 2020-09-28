<?php

use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;
use Clickfwd\Yoyo\Yoyo;
use function Tests\headers;
use function Tests\mockYoyoGetRequest;
use function Tests\yoyo_update;

beforeAll(function () {
    $yoyo = new Yoyo();

    $yoyo->configure([
        'namespace' => 'Tests\\App\\Yoyo\\',
    ]);

    require_once __DIR__.'/../app/Yoyo/Counter.php';

    $view = new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo'));

    $yoyo->setViewProvider($view);
});

test('pushed new URL state', function () {
    mockYoyoGetRequest('http://example.com/', 'counter/increment');

    yoyo_update();

    $headers = headers();

    expect($headers)->toHaveKey('HX-Push');

    expect($headers['HX-Push'])->toEqual('http://example.com/?count=1');
})->group('headers');
