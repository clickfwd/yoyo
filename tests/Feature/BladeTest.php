<?php

use Clickfwd\Yoyo\Yoyo;
use function Tests\htmlformat;
use function Tests\render;
use function Tests\update;
use function Tests\response;
use function Tests\yoyo_blade;

beforeAll(function () {
    yoyo_blade();
});

it('renders anonymous component', function () {
    expect(render('foo'))->toContain('blade foo');
});

it('updates anonymous component', function () {
    expect(update('foo'))->toContain('blade bar');
});

it('renders anonymous component form different location', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->getFinder()->flush();
    $view->prependLocation(__DIR__.'/../app-another/views');
    expect(render('foo'))->toContain('blade foo from another app');
});

it('renders anonymous component using a view namespace', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->getFinder()->flush();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    expect(render('packagename::foo'))->toContain('blade foo from another app');
});

it('renders dynamic component using a view and class namespace', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->getFinder()->flush();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    Yoyo::getInstance()->componentNamespace('packagename', 'Tests\\AppAnother\\Yoyo');
    expect(render('packagename::counter', ['count' => 3]))->toContain('The count is now 3');
});

it('can render nested components with @yoyo directive', function () {
    $output = render('parent', ['data'=>[1, 2, 3]], ['id'=>'parent']);
    expect(htmlformat($output))->toEqual(response('nested.blade'));
});
