<?php

use Clickfwd\Yoyo\Yoyo;
use Clickfwd\Yoyo\ComponentManager;
use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use function Tests\render;
use function Tests\update;
use function Tests\yoyo_view;

beforeAll(function () {
    yoyo_view();
});

test('errors when anonymous component template not found', function () {
    render('random');
})->throws(ComponentNotFound::class);

test('discovers and renders anonymous foo component', function () {
    expect(render('foo'))->toContain('foo');
});

test('updates anonymous foo component', function () {
    expect(update('foo'))->toContain('bar');
});

test('registered anonymous component is loaded', function () {
    \Clickfwd\Yoyo\Yoyo::registerComponent('registered-anon');
    expect(render('registered-anon'))->toContain('id="registered-anon"');
});

test('render anonymous component using a view namespace', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    expect(render('packagename::foo'))->toContain('other foo from another app');
});
