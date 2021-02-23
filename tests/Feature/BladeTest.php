<?php

use function Tests\render;
use function Tests\yoyo_blade;

beforeAll(function () {
    yoyo_blade();
});

test('discovers and renders anonymous foo component', function () {
    expect(render('foo'))->toContain('Foo');
});
