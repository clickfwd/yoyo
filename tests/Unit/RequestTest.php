<?php

use Clickfwd\Yoyo\Request;

beforeEach(function () {
    $_REQUEST = ['name' => 'test', 'count' => '5', 'data' => '{"key":"value"}'];
    $_SERVER = ['REQUEST_METHOD' => 'GET'];
});

it('returns all values with JSON decoded', function () {
    $request = new Request();
    $all = $request->all();
    expect($all['name'])->toBe('test');
    expect($all['data'])->toBe(['key' => 'value']);
});

it('returns same result on repeated all() calls', function () {
    $request = new Request();
    $first = $request->all();
    $second = $request->all();
    expect($first)->toEqual($second);
});

it('decodes JSON on get()', function () {
    $request = new Request();
    expect($request->get('data'))->toBe(['key' => 'value']);
});

it('returns default for missing key', function () {
    $request = new Request();
    expect($request->get('missing', 'default'))->toBe('default');
});

it('excludes specified keys', function () {
    $request = new Request();
    $result = $request->except(['name']);
    expect($result)->not->toHaveKey('name');
    expect($result)->toHaveKey('count');
});

it('returns only specified keys', function () {
    $request = new Request();
    $result = $request->only(['name']);
    expect($result)->toHaveKey('name');
    expect($result)->not->toHaveKey('count');
});

it('filters by prefix', function () {
    $_REQUEST = ['yoyo:id' => '123', 'yoyo:name' => 'test', 'other' => 'val'];
    $request = new Request();
    $result = $request->startsWith('yoyo:');
    expect($result)->toHaveKey('yoyo:id');
    expect($result)->toHaveKey('yoyo:name');
    expect($result)->not->toHaveKey('other');
});

it('reflects set() in subsequent get()', function () {
    $request = new Request();
    $request->set('new_key', 'new_val');
    expect($request->get('new_key'))->toBe('new_val');
});

it('reflects merge() in subsequent all()', function () {
    $request = new Request();
    $request->merge(['extra' => 'data']);
    $all = $request->all();
    expect($all)->toHaveKey('extra');
});

it('respects dropped keys', function () {
    $request = new Request();
    $request->drop('name');
    expect($request->get('name', 'default'))->toBe('default');
});

it('returns request method', function () {
    $request = new Request();
    expect($request->method())->toBe('GET');
});

it('detects yoyo request', function () {
    $_SERVER = ['HTTP_HX_REQUEST' => true];
    $request = new Request();
    expect($request->isYoyoRequest())->toBeTruthy();
});

it('returns header value', function () {
    $_SERVER = ['HTTP_CUSTOM_HEADER' => 'value'];
    $request = new Request();
    expect($request->header('CUSTOM_HEADER'))->toBe('value');
});

it('resets all data', function () {
    $request = new Request();
    $request->reset();
    expect($request->all())->toBeEmpty();
});

// --- Cache invalidation tests ---

it('invalidates all() cache after set()', function () {
    $request = new Request();
    $before = $request->all();

    $request->set('new_key', 'new_val');
    $after = $request->all();

    expect($after)->toHaveKey('new_key');
    expect($before)->not->toHaveKey('new_key');
});

it('invalidates all() cache after merge()', function () {
    $request = new Request();
    $before = $request->all();

    $request->merge(['merged' => 'data']);
    $after = $request->all();

    expect($after)->toHaveKey('merged');
});

it('invalidates all() cache after reset()', function () {
    $request = new Request();
    $request->all(); // populate cache
    $request->reset();

    expect($request->all())->toBeEmpty();
});

it('invalidates all() cache after mock()', function () {
    $request = new Request();
    $request->all(); // populate cache

    $request->mock(['mocked' => 'value'], ['REQUEST_METHOD' => 'POST']);
    $after = $request->all();

    expect($after)->toHaveKey('mocked');
    expect($after)->not->toHaveKey('name');
});

it('returns cached all() result (strict identity)', function () {
    $request = new Request();
    $first = $request->all();
    $second = $request->all();

    expect($first)->toBe($second);
});
