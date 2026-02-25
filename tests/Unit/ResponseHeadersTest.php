<?php

use Clickfwd\Yoyo\Services\Response;

it('sets HX-Location header', function () {
    $response = new Response();
    $result = $response->location('/new-path');

    expect($response->getHeaders())->toHaveKey('HX-Location');
    expect($response->getHeaders()['HX-Location'])->toBe('/new-path');
    expect($result)->toBe($response); // fluent interface
});

it('sets HX-Push-Url header', function () {
    $response = new Response();
    $response->pushUrl('/pushed');

    expect($response->getHeaders()['HX-Push-Url'])->toBe('/pushed');
});

it('sets HX-Redirect header', function () {
    $response = new Response();
    $response->redirect('/redirected');

    expect($response->getHeaders()['HX-Redirect'])->toBe('/redirected');
});

it('sets HX-Refresh header', function () {
    $response = new Response();
    $response->refresh();

    expect($response->getHeaders()['HX-Refresh'])->toBe('true');
});

it('sets HX-Replace-Url header', function () {
    $response = new Response();
    $response->replaceUrl('/replaced');

    expect($response->getHeaders()['HX-Replace-Url'])->toBe('/replaced');
});

it('sets HX-Reswap header', function () {
    $response = new Response();
    $response->reswap('innerHTML');

    expect($response->getHeaders()['HX-Reswap'])->toBe('innerHTML');
});

it('sets HX-Reselect header', function () {
    $response = new Response();
    $response->reselect('#content');

    expect($response->getHeaders()['HX-Reselect'])->toBe('#content');
});

it('sets HX-Retarget header', function () {
    $response = new Response();
    $response->retarget('#target');

    expect($response->getHeaders()['HX-Retarget'])->toBe('#target');
});

it('sets HX-Trigger header', function () {
    $response = new Response();
    $response->trigger('myEvent');

    expect($response->getHeaders()['HX-Trigger'])->toBe('myEvent');
});

it('sets HX-Trigger-After-Swap header', function () {
    $response = new Response();
    $response->triggerAfterSwap('swapEvent');

    expect($response->getHeaders()['HX-Trigger-After-Swap'])->toBe('swapEvent');
});

it('sets HX-Trigger-After-Settle header', function () {
    $response = new Response();
    $response->triggerAfterSettle('settleEvent');

    expect($response->getHeaders()['HX-Trigger-After-Settle'])->toBe('settleEvent');
});

// --- Response class tests ---

it('sets and retrieves status code', function () {
    $response = new Response();
    $response->status(404);

    expect($response->getStatusCode())->toBe(404);
});

it('defaults to 200 status code', function () {
    $response = new Response();

    expect($response->getStatusCode())->toBe(200);
});

it('returns content from send()', function () {
    $response = new Response();
    $result = $response->send('hello');

    expect($result)->toBe('hello');
});

it('returns empty string from send() with no content', function () {
    $response = new Response();
    $result = $response->send();

    expect($result)->toBe('');
});

it('merges headers with setHeaders()', function () {
    $response = new Response();
    $response->header('X-First', 'one');
    $response->setHeaders(['X-Second' => 'two', 'X-Third' => 'three']);

    $headers = $response->getHeaders();

    expect($headers)->toHaveKey('X-First');
    expect($headers)->toHaveKey('X-Second');
    expect($headers)->toHaveKey('X-Third');
});

it('supports fluent interface chaining', function () {
    $response = new Response();

    $result = $response
        ->status(201)
        ->header('X-Custom', 'value')
        ->location('/path')
        ->pushUrl('/url');

    expect($result)->toBe($response);
    expect($response->getStatusCode())->toBe(201);
    expect($response->getHeaders())->toHaveKey('X-Custom');
    expect($response->getHeaders())->toHaveKey('HX-Location');
    expect($response->getHeaders())->toHaveKey('HX-Push-Url');
});
