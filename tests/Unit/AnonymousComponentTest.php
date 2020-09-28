<?php

use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;
use Clickfwd\Yoyo\Yoyo;
use function Tests\render;
use function Tests\update;

beforeAll(function () {
    $yoyo = new Yoyo();

    $view = new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo'));

    $yoyo->setViewProvider($view);
});

test('errors when anonymous component template not found', function () {
    render('random');
})->throws(ComponentNotFound::class);

test('discovers and renders anonymous foo component', function () {
    expect(render('foo'))->toContain('Foo');
});

test('updates anonymous foo component', function () {
    expect(update('foo'))->toContain('Bar');
});
