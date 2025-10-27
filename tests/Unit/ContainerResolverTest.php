<?php

use Clickfwd\Yoyo\ContainerResolver;
use Clickfwd\Yoyo\Containers\YoyoContainer;
use Clickfwd\Yoyo\Containers\IlluminateContainer;

it('resolves to illuminate container when available', function() {
    $oldPreferred  = ContainerResolver::getPreferred();

    // Reset preferred
    ContainerResolver::setPreferred(null);
    $container = ContainerResolver::resolve();
    expect($container)->toBeInstanceOf(IlluminateContainer::class);

    // Cleanup
    ContainerResolver::setPreferred($oldPreferred);
});

it('uses preferred container when set', function() {
    $oldPreferred  = ContainerResolver::getPreferred();

    // Prefer YoyoContainer
    $preferred = YoyoContainer::getInstance();
    ContainerResolver::setPreferred($preferred);
    $container = ContainerResolver::resolve();
    expect($container)->toBe($preferred);

    // Prefer IlluminateContainer
    $preferred = IlluminateContainer::getInstance();
    ContainerResolver::setPreferred($preferred);
    $container = ContainerResolver::resolve();
    expect($container)->toBe($preferred);

    // Cleanup
    ContainerResolver::setPreferred($oldPreferred);
});
