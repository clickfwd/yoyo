<?php

use Clickfwd\Yoyo\Yoyo;
use Tests\App\Resolvers\BladeComponentResolver;
use function Tests\render;

test('render namespaced anonymous component', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    expect(render('packagename::foo'))->toContain('other foo from another app');
});

test('can render namespaced dynamic component', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    Yoyo::getInstance()->componentNamespace('packagename', 'Tests\\AppAnother\\Yoyo');
    expect(render('packagename::counter', ['count' => 3]))->toContain('The count is now 3');
});

test('can render namespaced dynamic component with custom resolver', function () {
    $yoyo = Yoyo::getInstance();
    $yoyo->registerComponentResolver(new BladeComponentResolver());
    $view = $yoyo->getViewProvider('blade');
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    $yoyo->componentNamespace('packagename', 'Tests\\AppAnother\\Yoyo');
    expect(render('packagename::counter', [
        'yoyo:resolver' => 'blade',
        'count' => 3
    ]))->toContain('The count is now 3');
});
