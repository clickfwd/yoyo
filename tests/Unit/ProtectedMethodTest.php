<?php

use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;
use function Tests\initYoyo;
use function Tests\update;

beforeAll(function () {
    $yoyo = initYoyo(['ProtectedMethods']);
});

test('component method is not public', function () {
    update('protected-methods', 'secret');
})->throws(NonPublicComponentMethodCall::class)->group('notpublic');
