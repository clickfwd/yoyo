<?php

use Clickfwd\Yoyo\Yoyo;
use function Tests\render;
use function Tests\yoyo_blade;

beforeAll(function () {
    yoyo_blade();
});

test('render anonymous component', function () {
    expect(render('foo'))->toContain('Foo');
});

test('render anonymous component form different location', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->getFinder()->flush();
    $view->prependLocation(__DIR__.'/../app-another/views');
    expect(render('foo'))->toContain('blade foo from another app');
});

test('render anonymous component using a view namespace', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->getFinder()->flush();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    expect(render('packagename::foo'))->toContain('blade foo from another app');
});

test('render dynamic component using a view and class namespace', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->getFinder()->flush();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    Yoyo::getInstance()->componentNamespace('packagename', 'Tests\\AppAnother\\Yoyo');
    expect(render('packagename::counter', ['count' => 3]))->toContain('The count is now 3');
});
