<?php

use function Tests\htmlformat;
use function Tests\mockYoyoGetRequest;
use function Tests\render;
use function Tests\resetYoyoRequest;
use function Tests\response;
use function Tests\yoyo_update;
use function Tests\yoyo_view;

uses()->group('unit-nested');

beforeAll(function () {
    yoyo_view();
});

it('can render nested components', function () {
    $output = render('parent', ['data' => [1, 2, 3]], ['id' => 'parent']);
    expect(htmlformat($output))->toEqual(response('nested'));
});

it('passes request values to a nested child without copying the parent component ID', function () {
    mockYoyoGetRequest('http://example.com/', 'parent-request-context', 'parent-context', [
        'yoyo-id' => 'parent-context',
        'selection' => 'requested',
        'menuId' => 'null',
    ]);

    try {
        $output = yoyo_update();
    } finally {
        resetYoyoRequest();
    }

    $output = htmlformat($output);

    expect($output)
        ->toContain('<span data-selection>requested</span>')
        ->toContain('<span data-menu-id>NULL</span>')
        ->toContain('id="child-context"');

    expect(preg_match('/<div id="child-context"[^>]*hx-vals=\'([^\']+)\'/', $output, $matches))->toBe(1);

    expect(json_decode($matches[1], true))->toBe([
        'yoyo-id' => 'child-context',
        'selection' => 'requested',
    ]);
});
