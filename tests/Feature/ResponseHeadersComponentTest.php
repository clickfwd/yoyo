<?php

use Clickfwd\Yoyo\Services\BrowserEventsService;
use Clickfwd\Yoyo\Services\Response;

use function Tests\headers;
use function Tests\mockYoyoGetRequest;
use function Tests\resetYoyoRequest;
use function Tests\yoyo_update;
use function Tests\yoyo_view;

uses()->group('response-headers');

beforeAll(function () {
    yoyo_view();
});

beforeEach(function () {
    $ref = new ReflectionClass(BrowserEventsService::class);
    $prop = $ref->getProperty('instance');
    $prop->setAccessible(true);
    $prop->setValue(null, null);

    $ref = new ReflectionClass(Response::class);
    $prop = $ref->getProperty('instance');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
});

afterEach(function () {
    resetYoyoRequest();
});

// --- Retarget ---

it('sets HX-Retarget header via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doRetarget', 'component-with-response-headers');

    $output = yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Retarget', '#other-target');
    expect($output)->toContain('retargeted');
});

// --- Reswap ---

it('sets HX-Reswap header via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doReswap', 'component-with-response-headers');

    $output = yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Reswap', 'innerHTML');
    expect($output)->toContain('reswapped');
});

// --- Reselect ---

it('sets HX-Reselect header via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doReselect', 'component-with-response-headers');

    $output = yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Reselect', '#selected-part');
    expect($output)->toContain('reselected');
});

// --- Location ---

it('sets HX-Location header via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doLocation', 'component-with-response-headers');

    yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Location', '/new-location');
});

// --- Push URL ---

it('sets HX-Push-Url header via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doPushUrl', 'component-with-response-headers');

    $output = yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Push-Url', '/pushed-url');
    expect($output)->toContain('url-pushed');
});

// --- Replace URL ---

it('sets HX-Replace-Url header via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doReplaceUrl', 'component-with-response-headers');

    $output = yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Replace-Url', '/replaced-url');
    expect($output)->toContain('url-replaced');
});

// --- Redirect (Response-level, not Component-level) ---

it('sets HX-Redirect header via response object', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doRedirect', 'component-with-response-headers');

    yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Redirect', '/redirected');
});

// --- Refresh ---

it('sets HX-Refresh header via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doRefresh', 'component-with-response-headers');

    yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Refresh', 'true');
});

// --- Trigger ---

it('sets HX-Trigger header via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doTrigger', 'component-with-response-headers');

    $output = yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Trigger', 'custom-event');
    expect($output)->toContain('triggered');
});

// --- Trigger After Swap ---

it('sets HX-Trigger-After-Swap header via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doTriggerAfterSwap', 'component-with-response-headers');

    $output = yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Trigger-After-Swap', 'swap-event');
    expect($output)->toContain('trigger-after-swap');
});

// --- Trigger After Settle ---

it('sets HX-Trigger-After-Settle header via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-response-headers/doTriggerAfterSettle', 'component-with-response-headers');

    $output = yoyo_update();

    $responseHeaders = headers();

    expect($responseHeaders)->toHaveKey('HX-Trigger-After-Settle', 'settle-event');
    expect($output)->toContain('trigger-after-settle');
});
