<?php

use Clickfwd\Yoyo\Services\PageRedirectService;
use Clickfwd\Yoyo\Services\Response;

uses()->group('services');

beforeEach(function () {
    // Reset singleton instances
    $ref = new ReflectionClass(PageRedirectService::class);
    $prop = $ref->getProperty('instance');
    $prop->setAccessible(true);
    $prop->setValue(null, null);

    $ref = new ReflectionClass(Response::class);
    $prop = $ref->getProperty('instance');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
});

it('sets redirect header for valid URL', function () {
    $service = PageRedirectService::getInstance();
    $service->redirect('/dashboard');

    $headers = Response::getInstance()->getHeaders();
    expect($headers)->toHaveKey('Yoyo-Redirect', '/dashboard');
});

it('sets redirect header for absolute URL', function () {
    $service = PageRedirectService::getInstance();
    $service->redirect('https://example.com/page');

    $headers = Response::getInstance()->getHeaders();
    expect($headers)->toHaveKey('Yoyo-Redirect', 'https://example.com/page');
});

it('does not set header for null URL', function () {
    $service = PageRedirectService::getInstance();
    $service->redirect(null);

    $headers = Response::getInstance()->getHeaders();
    expect($headers)->not->toHaveKey('Yoyo-Redirect');
});

it('does not set header for empty string URL', function () {
    $service = PageRedirectService::getInstance();
    $service->redirect('');

    $headers = Response::getInstance()->getHeaders();
    expect($headers)->not->toHaveKey('Yoyo-Redirect');
});

it('returns singleton instance', function () {
    $a = PageRedirectService::getInstance();
    $b = PageRedirectService::getInstance();
    expect($a)->toBe($b);
});
