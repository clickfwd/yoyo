<?php

use Clickfwd\Yoyo\View;

it('renders a template with variables', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');

    $output = $view->render('foo', ['spinning' => false]);

    expect($output)->toContain('default foo');
});

it('renders a template in spinning state', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');

    $output = $view->render('foo', ['spinning' => true]);

    expect($output)->toContain('default bar');
});

it('finds existing template via exists()', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');

    $path = $view->exists('foo');

    expect($path)->toBeString();
    expect($path)->toContain('foo.php');
});

it('caches template path on repeated exists() calls', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');

    $first = $view->exists('foo');
    $second = $view->exists('foo');

    expect($first)->toBe($second);
});

it('throws exception for non-existent template', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');

    $view->exists('nonexistent-template');
})->throws(InvalidArgumentException::class);

it('supports dot notation for subdirectories', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');

    $path = $view->exists('account.login');

    expect($path)->toContain('account/login.php');
});

it('adds location and finds templates there', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');
    $view->addLocation(__DIR__.'/../app-another/views');

    // The original location should still work
    $path = $view->exists('counter');
    expect($path)->toContain('app/resources/views/yoyo/counter.php');
});

it('prepends location with higher priority', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');
    $view->prependLocation(__DIR__.'/../app-another/views');

    // The prepended location should take priority
    $output = $view->render('foo', ['spinning' => false]);
    expect($output)->toContain('other foo from another app');
});

it('supports namespaced views', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');
    $view->addNamespace('pkg', __DIR__.'/../app-another/views');

    $path = $view->exists('pkg::foo');

    expect($path)->toContain('app-another/views/foo.php');
});

it('detects hint information in view names', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');

    expect($view->hasHintInformation('pkg::foo'))->toBeTrue();
    expect($view->hasHintInformation('foo'))->toBeFalse();
});

it('throws exception for unknown namespace', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');

    $view->exists('unknown::foo');
})->throws(InvalidArgumentException::class);

it('throws exception for invalid namespaced format', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');
    $view->addNamespace('pkg', __DIR__.'/../app-another/views');

    $view->exists('pkg::a::b');
})->throws(InvalidArgumentException::class);

it('prepends namespace hints with higher priority', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');
    $view->addNamespace('pkg', __DIR__.'/../app/resources/views/yoyo');
    $view->prependNamespace('pkg', __DIR__.'/../app-another/views');

    $output = $view->render('pkg::foo', ['spinning' => false]);
    expect($output)->toContain('other foo from another app');
});

it('throws exception for makeFromString with native view provider', function () {
    $view = new View(__DIR__.'/../app/resources/views/yoyo');

    $view->makeFromString('content', []);
})->throws(\Exception::class, 'Views from strings are not supported');

it('accepts multiple paths in constructor', function () {
    $view = new View([
        __DIR__.'/../app/resources/views/yoyo',
        __DIR__.'/../app-another/views',
    ]);

    // Should find templates from first path
    $path = $view->exists('counter');
    expect($path)->toContain('counter.php');
});
