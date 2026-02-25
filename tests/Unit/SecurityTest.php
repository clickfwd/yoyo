<?php

use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;
use Clickfwd\Yoyo\Request;
use Clickfwd\Yoyo\Services\Response;

use function Tests\mockYoyoGetRequest;
use function Tests\resetYoyoRequest;
use function Tests\update;
use function Tests\yoyo_update;
use function Tests\yoyo_view;

beforeAll(function () {
    yoyo_view();
});

// --- Header injection prevention ---

it('strips newlines from header names', function () {
    $response = new Response();
    $response->header("X-Custom\r\nInjected: bad", 'value');

    $headers = $response->getHeaders();

    // The key should have newlines stripped
    expect($headers)->toHaveKey('X-CustomInjected: bad');
    expect($headers)->not->toHaveKey("X-Custom\r\nInjected: bad");
});

it('strips newlines from header values', function () {
    $response = new Response();
    $response->header('X-Custom', "value\r\nInjected-Header: evil");

    $headers = $response->getHeaders();

    expect($headers['X-Custom'])->toBe('valueInjected-Header: evil');
    expect($headers['X-Custom'])->not->toContain("\r\n");
});

it('strips null bytes from header values', function () {
    $response = new Response();
    $response->header('X-Custom', "value\0hidden");

    $headers = $response->getHeaders();

    expect($headers['X-Custom'])->toBe('valuehidden');
});

it('preserves array header values without string sanitization', function () {
    $response = new Response();
    $response->header('Yoyo-Emit', ['event' => 'test']);

    $headers = $response->getHeaders();

    expect($headers['Yoyo-Emit'])->toBe(['event' => 'test']);
});

// --- Component action invocation security ---

it('prevents calling boot method via action', function () {
    update('counter', 'boot');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling setAction method via action', function () {
    update('counter', 'setAction');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling getComponentId method via action', function () {
    update('counter', 'getComponentId');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling getVariables method via action', function () {
    update('counter', 'getVariables');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling getName method via action', function () {
    update('counter', 'getName');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling getListeners method via action', function () {
    update('counter', 'getListeners');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling getProps method via action', function () {
    update('counter', 'getProps');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling getQueryString method via action', function () {
    update('counter', 'getQueryString');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling spinning method via action', function () {
    update('counter', 'spinning');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling set method via action', function () {
    update('counter', 'set');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling forgetComputed method via action', function () {
    update('counter', 'forgetComputed');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling skipRender method via action', function () {
    update('counter', 'skipRender');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling emit method via action', function () {
    update('counter', 'emit');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling __construct method via action', function () {
    update('counter', '__construct');
})->throws(NonPublicComponentMethodCall::class);

it('allows calling user-defined public action', function () {
    $output = update('counter', 'increment');
    expect($output)->toContain('The count is now 1');
});

it('throws ComponentMethodNotFound for non-existent method', function () {
    update('counter', 'nonExistentMethod');
})->throws(ComponentMethodNotFound::class);

// --- Request data isolation ---

it('isolates mocked request data from real $_SERVER', function () {
    $request = new Request();

    // Mock with no HTTP_HOST - should return null for fullUrl
    $request->mock(['component' => 'test'], []);

    expect($request->method())->toBe('GET'); // No REQUEST_METHOD defaults to GET
    expect($request->fullUrl())->toBeNull(); // No HTTP_HOST means null URL

    // Mock with full server data
    $request->mock(
        ['component' => 'test'],
        ['HTTP_HOST' => 'mocked.test', 'REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/mocked']
    );

    expect($request->method())->toBe('POST');
    expect($request->fullUrl())->toBe('http://mocked.test/mocked');

    // Mock again with different host - should use new mocked data, not previous
    $request->mock(
        [],
        ['HTTP_HOST' => 'other.test', 'REQUEST_URI' => '/other']
    );

    expect($request->fullUrl())->toBe('http://other.test/other');
});

// --- Component method access via URL path ---

it('prevents accessing protected methods through URL action parameter', function () {
    mockYoyoGetRequest('http://example.com/', 'protected-methods/secret');

    expect(fn () => yoyo_update())->toThrow(NonPublicComponentMethodCall::class);

    resetYoyoRequest();
});
