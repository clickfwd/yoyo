<?php

use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;
use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;
use Clickfwd\Yoyo\Yoyo;
use function Tests\encode_vars;
use function Tests\hxattr;
use function Tests\render;
use function Tests\update;
use function Tests\yoprefix_value;

beforeAll(function () {
    $yoyo = new Yoyo();

    $yoyo->configure([
      'namespace' => 'Tests\\App\\Yoyo\\',
    ]);

    require_once __DIR__.'/../app/Yoyo/Counter.php';

    $view = new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo'));

    $yoyo->setViewProvider($view);
});

test('component class not found', function () {
    render('random');
})->throws(ComponentNotFound::class);

test('counter component render', function () {
    $vars = encode_vars([yoprefix_value('id') => 'counter']);

    expect(render('counter'))->toContain(hxattr('vars', $vars));
});

test('counter component with default value', function () {
    $vars = encode_vars([yoprefix_value('id')=>'counter', 'count'=>3]);

    expect(render('counter', ['count'=>3]))->toContain(hxattr('vars', $vars));
});

test('counter component public method', function () {
    expect(update('counter', 'increment'))->toContain('The count is now 1');
});

test('counter component increment', function () {
    $vars = encode_vars([yoprefix_value('id')=>'counter', 'count'=>1]);

    expect(update('counter', 'increment'))->toContain(hxattr('vars', $vars));
});

test('counter component decrement', function () {
    $vars = encode_vars([yoprefix_value('id')=>'counter', 'count'=>-1]);

    expect(update('counter', 'decrement'))->toContain(hxattr('vars', $vars));
});

test('component method not found', function () {
    update('counter', 'random');
})->throws(ComponentMethodNotFound::class);