<?php

use function Tests\headers;
use function Tests\initYoyo;
use function Tests\mockYoyoGetRequest;
use function Tests\yoyo_update;

beforeAll(function () {
    $yoyo = initYoyo(['Counter']);
});

test('emitted browser event', function () {
    mockYoyoGetRequest('http://example.com/', 'counter/increment');

    yoyo_update();

    $headers = headers();

    expect($headers)->toHaveKey('Yoyo-Emit');

    expect($headers['Yoyo-Emit'])->toEqual('[{"event":"counter:updated","params":[{"count":1}]}]');
})->group('headers');
