<?php

use function Tests\render;
use function Tests\yoyo_twig;

beforeAll(function () {
    yoyo_twig();
});

test('discovers and renders anonymous foo component', function () {
    expect(render('foo'))->toContain('Foo');
});
