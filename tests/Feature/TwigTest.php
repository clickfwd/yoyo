<?php

use Clickfwd\Yoyo\Yoyo;

use function Tests\htmlformat;
use function Tests\render;
use function Tests\response;
use function Tests\update;
use function Tests\yoyo_twig;

beforeAll(function () {
    yoyo_twig();
});

it('discovers and renders anonymous foo component', function () {
    expect(render('foo'))->toContain('twig foo');
});

it('updates anonymous component', function () {
    expect(update('foo'))->toContain('twig bar');
});

it('can render anonymous component form different location', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->prependLocation(__DIR__.'/../app-another/views');
    expect(render('foo'))->toContain('twig foo from another app');
});

it('can render anonymous component using a view namespace', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    expect(render('packagename::foo'))->toContain('twig foo from another app');
});

it('can render dynamic component using a view and class namespace', function () {
    $view = Yoyo::getInstance()->getViewProvider();
    $view->addNamespace('packagename', __DIR__.'/../app-another/views');
    Yoyo::getInstance()->componentNamespace('packagename', 'Tests\\AppAnother\\Yoyo');
    expect(render('packagename::counter', ['count' => 3]))->toContain('The count is now 3');
});

it('can render nested components with yoyo function', function () {
    $output = render('parent', ['data' => [1, 2, 3]], ['id' => 'parent']);
    expect(htmlformat($output))->toEqual(response('nested.twig'));
});
