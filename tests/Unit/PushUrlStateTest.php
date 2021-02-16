<?php

use function Tests\headers;
use function Tests\mockYoyoGetRequest;
use function Tests\yoyo_update;

test('pushed new URL state', function () {
    mockYoyoGetRequest('http://example.com/', 'counter/increment');

    yoyo_update();

    $headers = headers();

    expect($headers)->toHaveKey('Yoyo-Push');

    expect($headers['Yoyo-Push'])->toEqual('http://example.com/?count=1');
})->group('headers');
