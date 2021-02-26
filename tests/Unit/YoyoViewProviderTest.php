<?php

use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;

test('can render a template', function () {
    $view = new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo'));
    expect((string) $view->render('foo', ['spinning' => false]))->toContain('default foo');
});

test('can render a template with a prepended location with higher priority', function () {
    $view = new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo'));
    $view->prependLocation(__DIR__.'/../app-another/views');
    expect((string) $view->render('foo'))->toContain('other foo from another app');
});

test('can render a template with custom namespace', function () {
    $view = new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo'));
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    expect((string) $view->render('packagename::foo'))->toContain('other foo from another app');
});
