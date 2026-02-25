<?php

use Clickfwd\Yoyo\Containers\IlluminateContainer;
use Clickfwd\Yoyo\Containers\YoyoContainer;
use Clickfwd\Yoyo\Exceptions\BindingNotFoundException;
use Clickfwd\Yoyo\Exceptions\ContainerResolutionException;
use Clickfwd\Yoyo\Interfaces\YoyoContainerInterface;

it('can set and get bindings', function () {
    $container = new YoyoContainer();
    $container->set('test', 'value');

    expect($container->get('test'))->toBe('value');
    expect($container->has('test'))->toBeTrue();
});

it('can make classes with dependencies', function () {
    $container = new YoyoContainer();

    $instance = $container->make(Tests\App\Yoyo\Counter::class, ['id' => 'test', 'name' => 'test']);

    expect($instance)->toBeInstanceOf(Tests\App\Yoyo\Counter::class);
});

it('handles nullable parameters correctly', function () {
    $container = new YoyoContainer();

    $class = new class (null) {
        public ?string $optional;

        public function __construct(?string $optional)
        {
            $this->optional = $optional;
        }
    };

    $instance = $container->make(get_class($class)); // Injects null implicitly
    expect($instance)->toBeObject();
    expect($instance->optional)->toBeNull();

    $instance = $container->make(get_class($class), ['optional' => 'test']);
    expect($instance)->toBeObject();
    expect($instance->optional)->toBe('test');
});

it('handles interfaces bound to container', function () {
    $container = new YoyoContainer();

    $class = new class ($container) {
        public YoyoContainerInterface $container;

        public function __construct(YoyoContainerInterface $container)
        {
            $this->container = $container;
        }
    };

    // Binds class name to interface
    $container->set(YoyoContainerInterface::class, YoyoContainer::class);
    $instance = $container->make(get_class($class));
    expect($instance)->toBeObject();
    expect($instance->container)->toBeInstanceOf(YoyoContainer::class);

    // Binds class instance to interface
    $container->set(YoyoContainerInterface::class, IlluminateContainer::getInstance());
    $instance = $container->make(get_class($class));
    expect($instance)->toBeObject();
    expect($instance->container)->toBeInstanceOf(IlluminateContainer::class);
});

it('resolves closures as singletons', function () {
    $container = new YoyoContainer();
    $counter = 0;

    $container->set('service', function () use (&$counter) {
        $counter++;
        return new stdClass();
    });

    $first = $container->get('service');
    $second = $container->get('service');

    expect($counter)->toBe(1); // Closure only called once
    expect($first)->toBe($second); // Same instance
});

it('throws exception for failed resolution', function () {
    $container = new YoyoContainer();

    $this->expectException(ContainerResolutionException::class);
    $container->make(Foo::class);
});

it('throws exception for invalid bindings', function () {
    $container = new YoyoContainer();

    $this->expectException(BindingNotFoundException::class);
    $container->get(Foo::class);
});
