<?php

use Clickfwd\Yoyo\Yoyo;
use Tests\App\Resolvers\CustomComponentResolver;
use Tests\App\Resolvers\BladeComponentResolver;
use Tests\App\Resolvers\TwigComponentResolver;
use function Tests\render;

test('can use multiple view providers using component resolvers', function () {
    $yoyo = Yoyo::getInstance();
    $yoyo->registerComponentResolver(new CustomComponentResolver());
    $yoyo->registerComponentResolver(new BladeComponentResolver());
    $yoyo->registerComponentResolver(new TwigComponentResolver());

    expect(render('foo', ['yoyo:resolver' => 'custom']))->toContain('default foo')
        ->and(render('foo', ['yoyo:resolver' => 'blade']))->toContain('blade foo')
        ->and(render('foo', ['yoyo:resolver' => 'twig']))->toContain('twig foo');
});
