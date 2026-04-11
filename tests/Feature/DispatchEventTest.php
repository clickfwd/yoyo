<?php

use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Services\BrowserEventsService;
use Clickfwd\Yoyo\Services\Response;

use function Tests\mockYoyoGetRequest;
use function Tests\resetYoyoRequest;
use function Tests\yoyo_update;
use function Tests\yoyo_view;

uses()->group('dispatch');

beforeAll(function () {
    yoyo_view();
});

beforeEach(function () {
    // Reset singletons for clean state (mirrors ComponentLifecycleTest)
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

// =============================================================================
// JS Dispatch - Associative (named) params: Yoyo.dispatch('event', { key: val })
// =============================================================================

it('handles JS dispatch with single named parameter', function () {
    // Simulates: Yoyo.dispatch('post-created', { postId: 42 })
    mockYoyoGetRequest('http://example.com/', 'dispatch-listener/post-created', '', [
        'eventParams' => json_encode(['postId' => 42]),
    ]);

    $output = yoyo_update();

    expect($output)->toContain('Post created with ID: 42');
});

it('handles JS dispatch with multiple named parameters', function () {
    // Simulates: Yoyo.dispatch('status-changed', { status: 'active', reason: 'manual' })
    mockYoyoGetRequest('http://example.com/', 'dispatch-listener/status-changed', '', [
        'eventParams' => json_encode(['status' => 'active', 'reason' => 'manual']),
    ]);

    $output = yoyo_update();

    expect($output)->toContain('Status: active, Reason: manual');
});

it('handles JS dispatch with named params where optional param is omitted', function () {
    // Simulates: Yoyo.dispatch('status-changed', { status: 'paused' })
    // 'reason' has a default value of 'none', should use it
    mockYoyoGetRequest('http://example.com/', 'dispatch-listener/status-changed', '', [
        'eventParams' => json_encode(['status' => 'paused']),
    ]);

    $output = yoyo_update();

    expect($output)->toContain('Status: paused, Reason: none');
});

// =============================================================================
// JS Dispatch - No params: Yoyo.dispatch('event')
// =============================================================================

it('handles JS dispatch without parameters', function () {
    // Simulates: Yoyo.dispatch('simple-refresh') with explicit empty object
    // Must use stdClass to produce JSON object '{}' matching JS JSON.stringify({})
    // json_encode([]) produces '[]' which is the server-side emit case — different path
    mockYoyoGetRequest('http://example.com/', 'dispatch-listener/simple-refresh', '', [
        'eventParams' => json_encode(new stdClass()),
    ]);

    $output = yoyo_update();

    expect($output)->toContain('Refreshed without params');
});

// =============================================================================
// JS Dispatch - Missing required parameter
// =============================================================================

it('throws exception when required named parameter is missing', function () {
    // Simulates: Yoyo.dispatch('status-changed', { reason: 'manual' })
    // 'status' is required but missing from the payload
    mockYoyoGetRequest('http://example.com/', 'dispatch-listener/status-changed', '', [
        'eventParams' => json_encode(['reason' => 'manual']),
    ]);

    yoyo_update();
})->throws(\InvalidArgumentException::class);

// =============================================================================
// Server-side emit - Sequential (positional) params (existing behavior)
// =============================================================================

it('handles server-side emit with sequential parameters', function () {
    // Simulates server-side: $this->emit('post-created', 99)
    // Server emit sends params as numerically indexed array
    mockYoyoGetRequest('http://example.com/', 'dispatch-listener/post-created', '', [
        'eventParams' => json_encode([99]),
    ]);

    $output = yoyo_update();

    expect($output)->toContain('Post created with ID: 99');
});

it('handles server-side emit with multiple sequential parameters', function () {
    // Simulates: $this->emit('multi-param', 'My Title', 'My Body', 5)
    mockYoyoGetRequest('http://example.com/', 'dispatch-listener/multi-param', '', [
        'eventParams' => json_encode(['My Title', 'My Body', 5]),
    ]);

    $output = yoyo_update();

    expect($output)->toContain('Title: My Title, Body: My Body, Category: 5');
});

// =============================================================================
// Security - only declared listeners can be triggered
// =============================================================================

it('rejects dispatch to non-listener event name', function () {
    // Event name 'nonExistentEvent' is not declared in $listeners
    // Exception fires during listener routing, before eventParams are read
    mockYoyoGetRequest('http://example.com/', 'dispatch-listener/nonExistentEvent', '', []);

    yoyo_update();
})->throws(ComponentMethodNotFound::class);
