<?php

use Illuminate\Container\Container;

test('finds resolver source',  function() {
    $resolver = new Clickfwd\Yoyo\ComponentResolver(Container::getInstance(), $registered = [], [
        'yoyo:source' => 'path.to.component'
    ]);

    expect($resolver->source())->toBe('path.to.component');
});

test('resolves dynamic component by name',  function() {
    $resolver = new Clickfwd\Yoyo\ComponentResolver(Container::getInstance(), $registered = [], [
        'yoyo:source' => 'path.to.component'
    ]);

    expect($resolver->resolveDynamic('counter', 'counter'))->toBeInstanceOf(Tests\App\Yoyo\Counter::class);
});

test('resolves anonymous component by name',  function() {
    $resolver = new Clickfwd\Yoyo\ComponentResolver(Container::getInstance(), $registered = [], [
        'yoyo:source' => 'path.to.component'
    ]);

    expect($resolver->resolveAnonymous('foo', 'foo'))->toBeInstanceOf(Clickfwd\Yoyo\AnonymousComponent::class);
});

