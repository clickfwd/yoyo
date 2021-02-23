<?php

use Clickfwd\Yoyo\ComponentManager;
use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use function Tests\render;
use function Tests\update;

test('errors when anonymous component template not found', function () {
    render('random');
})->throws(ComponentNotFound::class);

test('discovers and renders anonymous foo component', function () {
    expect(render('foo'))->toContain('Foo');
});

test('updates anonymous foo component', function () {
    expect(update('foo'))->toContain('Bar');
});

test('registered anonymous component is loaded', function () {
    \Clickfwd\Yoyo\Yoyo::registerComponent('registered-anon');
    expect(render('registered-anon'))->toContain('id="registered-anon"');
});
