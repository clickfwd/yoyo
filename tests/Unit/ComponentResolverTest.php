<?php

use Illuminate\Container\Container;

test('resolves dynamic component', function () {
    $resolver = (new Clickfwd\Yoyo\ComponentResolver())(Container::getInstance());

    expect($resolver->resolveDynamic('counter', 'counter'))->toBeInstanceOf(Tests\App\Yoyo\Counter::class);
});

test('resolves anonymous component', function () {
    $resolver = (new Clickfwd\Yoyo\ComponentResolver())(Container::getInstance());

    expect($resolver->resolveAnonymous('foo', 'foo'))->toBeInstanceOf(Clickfwd\Yoyo\AnonymousComponent::class);
});

test('resolves namespaced dynamic component', function () {
    $namespaces = ['packagename' => 'Tests\\AppAnother\\Yoyo'];
    $resolver = (new Clickfwd\Yoyo\ComponentResolver())(Container::getInstance(), [], $namespaces);

    expect($resolver->resolveDynamic('foo', 'packagename::counter'))
        ->toBeInstanceOf(Tests\AppAnother\Yoyo\Counter::class);
});
