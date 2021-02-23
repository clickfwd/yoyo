<?php

use function Tests\headers;
use function Tests\mockYoyoGetRequest;
use function Tests\yoyo_update;
use function Tests\yoyo_view;

beforeAll(function () {
    yoyo_view();
});

test('emitted browser event', function () {
    mockYoyoGetRequest('http://example.com/', 'counter/increment');
    
    yoyo_update();

    $headers = headers();

    expect($headers)->toHaveKey('Yoyo-Emit');

    expect($headers['Yoyo-Emit'])->toEqual('[{"event":"counter:updated","params":[{"count":1}]}]');
})->group('headers');
