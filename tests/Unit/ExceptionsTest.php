<?php

use Clickfwd\Yoyo\Exceptions\BindingNotFoundException;
use Clickfwd\Yoyo\Exceptions\BypassRenderMethod;
use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use Clickfwd\Yoyo\Exceptions\ContainerResolutionException;
use Clickfwd\Yoyo\Exceptions\FailedToRegisterComponent;
use Clickfwd\Yoyo\Exceptions\HttpException;
use Clickfwd\Yoyo\Exceptions\IncompleteComponentParamInRequest;
use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;
use Clickfwd\Yoyo\Exceptions\NotFoundHttpException;

// --- HttpException ---

it('creates HttpException with status code and message', function () {
    $e = new HttpException(500, 'Internal error', ['X-Custom' => 'value']);

    expect($e->getStatusCode())->toBe(500);
    expect($e->getMessage())->toBe('Internal error');
    expect($e->getHeaders())->toBe(['X-Custom' => 'value']);
    expect($e->getCode())->toBe(500);
});

it('creates HttpException with empty message', function () {
    $e = new HttpException(403);

    expect($e->getStatusCode())->toBe(403);
    expect($e->getMessage())->toBe('');
    expect($e->getHeaders())->toBe([]);
});

// --- NotFoundHttpException ---

it('creates NotFoundHttpException with 404 status', function () {
    $e = new NotFoundHttpException('Page not found', ['Retry-After' => '60']);

    expect($e->getStatusCode())->toBe(404);
    expect($e->getMessage())->toBe('Page not found');
    expect($e->getHeaders())->toBe(['Retry-After' => '60']);
});

it('creates NotFoundHttpException with defaults', function () {
    $e = new NotFoundHttpException();

    expect($e->getStatusCode())->toBe(404);
    expect($e->getMessage())->toBe('');
});

// --- BypassRenderMethod ---

it('creates BypassRenderMethod with status code', function () {
    $e = new BypassRenderMethod(204);

    expect($e->getCode())->toBe(204);
    expect($e->getMessage())->toBe('');
});

it('creates BypassRenderMethod with 200 status', function () {
    $e = new BypassRenderMethod(200);

    expect($e->getCode())->toBe(200);
});

// --- ComponentMethodNotFound ---

it('creates ComponentMethodNotFound with descriptive message', function () {
    $e = new ComponentMethodNotFound('Counter', 'nonExistent');

    expect($e->getMessage())->toContain('nonExistent');
    expect($e->getMessage())->toContain('Counter');
    expect($e->getMessage())->toContain('Public method');
});

// --- ComponentNotFound ---

it('creates ComponentNotFound with component alias', function () {
    $e = new ComponentNotFound('missing-component');

    expect($e->getMessage())->toContain('missing-component');
    expect($e->getMessage())->toContain('not found');
});

// --- NonPublicComponentMethodCall ---

it('creates NonPublicComponentMethodCall with descriptive message', function () {
    $e = new NonPublicComponentMethodCall('Counter', 'secret');

    expect($e->getMessage())->toContain('Counter');
    expect($e->getMessage())->toContain('secret');
    expect($e->getMessage())->toContain('non-public');
});

// --- IncompleteComponentParamInRequest ---

it('creates IncompleteComponentParamInRequest with default message', function () {
    $e = new IncompleteComponentParamInRequest();

    expect($e->getMessage())->toContain('component parameter');
    expect($e->getMessage())->toContain('missing');
});

// --- FailedToRegisterComponent ---

it('creates FailedToRegisterComponent for anonymous component', function () {
    $e = new FailedToRegisterComponent('my-alias', 'Anonymous');

    expect($e->getMessage())->toContain('my-alias');
    expect($e->getMessage())->toContain('Anonymous');
    expect($e->getMessage())->toContain('template not found');
});

it('creates FailedToRegisterComponent for class-based component', function () {
    $e = new FailedToRegisterComponent('my-alias', 'App\\Yoyo\\MyComponent');

    expect($e->getMessage())->toContain('my-alias');
    expect($e->getMessage())->toContain('App\\Yoyo\\MyComponent');
    expect($e->getMessage())->toContain('class');
    expect($e->getMessage())->toContain('not found');
});

// --- BindingNotFoundException ---

it('creates BindingNotFoundException with message and previous exception', function () {
    $previous = new \RuntimeException('original');
    $e = new BindingNotFoundException('binding not found', $previous);

    expect($e->getMessage())->toBe('binding not found');
    expect($e->getPrevious())->toBe($previous);
    expect($e)->toBeInstanceOf(\Psr\Container\NotFoundExceptionInterface::class);
});

// --- ContainerResolutionException ---

it('creates ContainerResolutionException with message and previous exception', function () {
    $previous = new \RuntimeException('original');
    $e = new ContainerResolutionException('resolution failed', $previous);

    expect($e->getMessage())->toBe('resolution failed');
    expect($e->getPrevious())->toBe($previous);
    expect($e)->toBeInstanceOf(\Psr\Container\ContainerExceptionInterface::class);
});
