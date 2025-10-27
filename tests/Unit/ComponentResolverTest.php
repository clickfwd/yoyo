<?php

use Clickfwd\Yoyo\ContainerResolver;

it('resolves dynamic component', function () {
    $resolver = (new Clickfwd\Yoyo\ComponentResolver())(ContainerResolver::resolve());

    expect($resolver->resolveDynamic('counter', 'counter'))->toBeInstanceOf(Tests\App\Yoyo\Counter::class);
});

it('resolves anonymous component', function () {
    $resolver = (new Clickfwd\Yoyo\ComponentResolver())(ContainerResolver::resolve());

    expect($resolver->resolveAnonymous('foo', 'foo'))->toBeInstanceOf(Clickfwd\Yoyo\AnonymousComponent::class);
});

it('resolves namespaced dynamic component', function () {
    $namespaces = ['packagename' => [
        'Tests\\AppAnother\\Yoyo',
    ]];
    
    $resolver = (new Clickfwd\Yoyo\ComponentResolver())(ContainerResolver::resolve(), [], $namespaces);

    expect($resolver->resolveDynamic('foo', 'packagename::counter'))
        ->toBeInstanceOf(Tests\AppAnother\Yoyo\Counter::class);
});
