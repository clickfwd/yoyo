<?php

use Clickfwd\Yoyo\View;;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;

test('can render a template', function() {
    $view = new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo'));
    expect(preg_replace( "/\n|\s/", "", $view->render('foo', ['spinning' => false])))->toBe('<div>Foo</div>');
});

test('can render a template with a prepended location with higher priority', function() {
    $view = new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo'));
    $view->prependLocation(__DIR__.'/../app-another/views');
    expect((string) $view->render('foo'))->toBe('<div>foo from another app</div>');
});

test('can render a template with custom namespace', function() {
    $view = new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo'));
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    expect((string) $view->render('packagename::foo'))->toBe('<div>foo from another app</div>');
});
