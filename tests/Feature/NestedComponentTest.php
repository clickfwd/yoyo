<?php

use function Tests\htmlformat;
use function Tests\render;
use function Tests\response;

uses()->group('unit-nested');

test('nested component renders correctly', function () {
    $output = render('parent', ['data'=>[1, 2, 3]], ['id'=>'parent']);
    expect(htmlformat($output))->toEqual(response('nested'));
});
