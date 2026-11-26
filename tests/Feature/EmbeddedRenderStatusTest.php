<?php

use Clickfwd\Yoyo\Exceptions\BypassRenderMethod;
use Clickfwd\Yoyo\Exceptions\HttpException;
use Clickfwd\Yoyo\Services\Response;

use function Tests\headers;
use function Tests\mockYoyoGetRequest;
use function Tests\render;
use function Tests\resetYoyoRequest;
use function Tests\yoyo_update;
use function Tests\yoyo_view;

beforeAll(function () {
    yoyo_view();
});

beforeEach(function () {
    (new ReflectionClass(Response::class))->getProperty('instance')->setValue(null, null);

    http_response_code(404);
});

afterEach(function () {
    resetYoyoRequest();
});

it('keeps the host status when embedded in a page', function () {
    expect(render('counter'))->toContain('id="counter"');

    expect(http_response_code())->toBe(404);
});

it('keeps the host status when an embedded component skips rendering', function () {
    expect(fn () => render('empty-response'))->toThrow(BypassRenderMethod::class);

    expect(http_response_code())->toBe(404);
});

it('keeps the host status when an embedded component skips rendering and removes itself', function () {
    expect(render('empty-response-and-remove'))->toBe('');

    expect(http_response_code())->toBe(404);
});

it('keeps the host status when an embedded component aborts', function () {
    http_response_code(410);

    expect(fn () => render('abort'))->toThrow(HttpException::class);

    expect(http_response_code())->toBe(410);
});

it('sets the status on an update request', function () {
    mockYoyoGetRequest('http://example.com/', 'counter', 'counter');

    expect(yoyo_update())->toContain('id="counter"');

    expect(http_response_code())->toBe(200);
});

it('sets the 204 status on an update request that skips rendering', function () {
    mockYoyoGetRequest('http://example.com/', 'empty-response', 'empty-response');

    expect(fn () => yoyo_update())->toThrow(BypassRenderMethod::class);

    expect(http_response_code())->toBe(204);
});

it('sets the 200 status and the swap header on an update request that skips rendering and removes itself', function () {
    mockYoyoGetRequest('http://example.com/', 'empty-response-and-remove', 'empty-response-and-remove');

    expect(yoyo_update())->toBe('');

    expect(http_response_code())->toBe(200);

    expect(headers())->toHaveKey('Yoyo-Swap-Modifier', 'swap:1s');
});

it('sets the status on an update request that aborts', function () {
    mockYoyoGetRequest('http://example.com/', 'abort', 'abort');

    http_response_code(200);

    expect(fn () => yoyo_update())->toThrow(HttpException::class);

    expect(http_response_code())->toBe(404);
});
