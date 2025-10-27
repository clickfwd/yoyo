<?php

use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;

use function Tests\update;

it('throws exception when requesting a protected component action', function () {
    update('protected-methods', 'secret');
})->throws(NonPublicComponentMethodCall::class)->group('notpublic');
