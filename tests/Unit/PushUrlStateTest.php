<?php

use function Tests\headers;
use function Tests\initYoyo;
use function Tests\mockYoyoGetRequest;
use function Tests\yoyo_update;

beforeAll(function () {
    $yoyo = initYoyo(['Counter']);
});

test('pushed new URL state', function () {
    mockYoyoGetRequest('http://example.com/', 'counter/increment');

    yoyo_update();

    $headers = headers();

    expect($headers)->toHaveKey('HX-Push');

    expect($headers['HX-Push'])->toEqual('http://example.com/?count=1');
})->group('headers');
