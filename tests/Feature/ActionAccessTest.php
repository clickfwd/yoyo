<?php

use Clickfwd\Yoyo\Containers\IlluminateContainer;
use Clickfwd\Yoyo\Containers\YoyoContainer;
use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;
use Clickfwd\Yoyo\Yoyo;
use Illuminate\Container\Container;

use function Tests\mockYoyoGetRequest;
use function Tests\resetYoyoRequest;
use function Tests\yoyo_update;
use function Tests\yoyo_view;

beforeEach(function () {
    $this->previousContainer = Yoyo::container();
});

afterEach(function () {
    resetYoyoRequest();
    new Yoyo($this->previousContainer);
});

dataset('action access containers', [
    'native' => ['native'],
    'illuminate' => ['illuminate'],
]);

it('blocks framework helpers regardless of method spelling', function ($container, $action) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'action-access/'.$action);

    expect(fn () => yoyo_update())->toThrow(NonPublicComponentMethodCall::class);
})->with('action access containers')->with([
    'getName', 'getname', 'GETNAME', 'GeTnAmE',
    'getComponentId', 'GETCOMPONENTID',
    'skipRender', 'SKIPRENDER',
    'internal-helper',
    '__get', '__GET',
    'emit', 'EMIT',
]);

it('preserves case-insensitive public actions and mapped listener methods', function ($container, $action) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'action-access/'.$action);

    expect(yoyo_update())->toContain('count=1; renders=1');
})->with('action access containers')->with(['increment', 'INCREMENT', 'InCrEmEnT', 'CountChanged']);

it('renders once for render actions and refresh listeners', function ($container, $action) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'action-access/'.$action);

    expect(yoyo_update())->toContain('count=0; renders=1');
})->with('action access containers')->with(['render', 'RENDER', 'ReNdEr', 'redraw', 'refresh', 'reload']);

it('keeps listener event names case-sensitive', function ($container) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'action-access/countchanged');

    expect(fn () => yoyo_update())->toThrow(ComponentMethodNotFound::class);
})->with('action access containers');
