<?php

use function Tests\htmlformat;
use function Tests\render;
use function Tests\response;
use function Tests\yoyo_view;

uses()->group('unit-nested');

beforeAll(function () {
    yoyo_view();
});

it('can render nested components', function () {
    $output = render('parent', ['data' => [1, 2, 3]], ['id' => 'parent']);
    expect(htmlformat($output))->toEqual(response('nested'));
});
