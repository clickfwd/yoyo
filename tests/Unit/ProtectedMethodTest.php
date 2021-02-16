<?php

use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;
use function Tests\update;

test('component method is not public', function () {
    update('protected-methods', 'secret');
})->throws(NonPublicComponentMethodCall::class)->group('notpublic');
