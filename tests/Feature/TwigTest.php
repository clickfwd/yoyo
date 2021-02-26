<?php

use Clickfwd\Yoyo\Yoyo;
use function Tests\render;
use function Tests\yoyo_twig;

beforeAll(function () {
    yoyo_twig();
});

test('discovers and renders anonymous foo component', function () {
    expect(render('foo'))->toContain('twig foo');
});

test('render anonymous component form different location', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->prependLocation(__DIR__.'/../app-another/views');
    expect(render('foo'))->toContain('twig foo from another app');
});

test('render anonymous component using a view namespace', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    expect(render('packagename::foo'))->toContain('twig foo from another app');
});

test('render dynamic component using a view and class namespace', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    Yoyo::getInstance()->componentNamespace('packagename', 'Tests\\AppAnother\\Yoyo');
    expect(render('packagename::counter', ['count' => 3]))->toContain('The count is now 3');
});
