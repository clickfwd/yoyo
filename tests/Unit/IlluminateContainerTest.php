<?php

use Clickfwd\Yoyo\Containers\IlluminateContainer;
use Illuminate\Container\Container;

it('delegates to illuminate container', function () {
    $illuminate = Container::getInstance();
    $illuminate->bind('test', fn () => 'value');

    $container = IlluminateContainer::getInstance();

    expect($container->get('test'))->toBe('value');
});
