<?php

use Clickfwd\Yoyo\Services\Response;
use Clickfwd\Yoyo\Services\UrlStateManagerService;
use Clickfwd\Yoyo\Yoyo;

uses()->group('services');

beforeEach(function () {
    $ref = new ReflectionClass(Response::class);
    $prop = $ref->getProperty('instance');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
});

afterEach(function () {
    Yoyo::request()->reset();
});

it('sets push state header when URL changes', function () {
    Yoyo::request()->mock([], [
        'REQUEST_METHOD' => 'GET',
        'HTTP_HX_CURRENT_URL' => 'http://example.com/page',
    ]);

    $service = new UrlStateManagerService();
    $service->pushState(['count' => 1]);

    $headers = Response::getInstance()->getHeaders();
    expect($headers)->toHaveKey('Yoyo-Push');
    expect($headers['Yoyo-Push'])->toContain('count=1');
});

it('does not set push header on POST requests', function () {
    Yoyo::request()->mock([], [
        'REQUEST_METHOD' => 'POST',
        'HTTP_HX_CURRENT_URL' => 'http://example.com/page',
    ]);

    $service = new UrlStateManagerService();
    $service->pushState(['count' => 1]);

    $headers = Response::getInstance()->getHeaders();
    expect($headers)->not->toHaveKey('Yoyo-Push');
});

it('does not set push header when URL is null', function () {
    Yoyo::request()->mock([], [
        'REQUEST_METHOD' => 'GET',
    ]);

    $service = new UrlStateManagerService();
    $service->pushState(['count' => 1]);

    $headers = Response::getInstance()->getHeaders();
    expect($headers)->not->toHaveKey('Yoyo-Push');
});

it('does not set push header when URL stays the same', function () {
    Yoyo::request()->mock([], [
        'REQUEST_METHOD' => 'GET',
        'HTTP_HX_CURRENT_URL' => 'http://example.com/page',
    ]);

    $service = new UrlStateManagerService();
    $service->pushState([]);

    $headers = Response::getInstance()->getHeaders();
    expect($headers)->not->toHaveKey('Yoyo-Push');
});

it('preserves port in push URL', function () {
    Yoyo::request()->mock([], [
        'REQUEST_METHOD' => 'GET',
        'HTTP_HX_CURRENT_URL' => 'http://localhost:8080/page',
    ]);

    $service = new UrlStateManagerService();
    $service->pushState(['foo' => 'bar']);

    $headers = Response::getInstance()->getHeaders();
    expect($headers['Yoyo-Push'])->toContain('localhost:8080');
});

it('builds URL without query string when no params', function () {
    Yoyo::request()->mock([], [
        'REQUEST_METHOD' => 'GET',
        'HTTP_HX_CURRENT_URL' => 'http://example.com/page?old=param',
    ]);

    $service = new UrlStateManagerService();
    $service->pushState([]);

    $headers = Response::getInstance()->getHeaders();
    // When pushState with empty params, the new URL has no query string
    // This differs from current URL so it should set the header
    expect($headers)->toHaveKey('Yoyo-Push');
    expect($headers['Yoyo-Push'])->toBe('http://example.com/page');
});
