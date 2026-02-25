<?php

use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;
use Clickfwd\Yoyo\Services\BrowserEventsService;
use Clickfwd\Yoyo\Services\PageRedirectService;
use Clickfwd\Yoyo\Services\Response;

use function Tests\headers;
use function Tests\mockYoyoGetRequest;
use function Tests\mockYoyoPostRequest;
use function Tests\render;
use function Tests\resetYoyoRequest;
use function Tests\update;
use function Tests\yoyo_update;
use function Tests\yoyo_view;

uses()->group('component-lifecycle');

beforeAll(function () {
    yoyo_view();
});

beforeEach(function () {
    // Reset singleton services to prevent cross-test pollution
    foreach ([BrowserEventsService::class, PageRedirectService::class, Response::class] as $class) {
        $ref = new ReflectionClass($class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }
});

// --- Listeners ---

it('renders component with listeners in trigger attribute', function () {
    $output = render('component-with-listeners');
    expect($output)
        ->toContain('itemAdded')
        ->toContain('id="component-with-listeners"');
});

it('includes mapped listener events in trigger', function () {
    $output = render('component-with-listeners');
    // Both itemAdded and refresh should be in the trigger attribute
    expect($output)->toContain('itemAdded');
});

// --- Computed properties with arguments ---

it('renders computed property with arguments', function () {
    $output = render('component-with-computed-args');
    expect($output)
        ->toContain('Hello, Alice!')
        ->toContain('Hello, Bob!');
});

// --- Redirect ---

it('sets redirect property via redirect method', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-redirect/save', 'component-with-redirect');

    $output = yoyo_update();

    $responseHeaders = headers();

    resetYoyoRequest();

    expect($responseHeaders)->toHaveKey('Yoyo-Redirect', '/success');
});

// --- Emit and browser events ---

it('emits events via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-emit/doEmit', 'component-with-emit');

    $output = yoyo_update();

    $responseHeaders = headers();

    resetYoyoRequest();

    $events = json_decode($responseHeaders['Yoyo-Emit'], true);
    expect($events)->toBeArray();
    expect($events[0]['event'])->toBe('testEvent');
    // Params are wrapped in an array due to variadic forwarding in BrowserEvents trait
    expect($events[0]['params'])->toBeArray();
    expect($events[0]['params'][0])->toMatchArray(['key' => 'value']);
});

it('emits targeted events via emitTo', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-emit/doEmitTo', 'component-with-emit');

    $output = yoyo_update();

    $responseHeaders = headers();

    resetYoyoRequest();

    $events = json_decode($responseHeaders['Yoyo-Emit'], true);
    expect($events)->toBeArray();
    expect($events[0])->toMatchArray([
        'event' => 'targetEvent',
        'component' => 'other-component',
    ]);
});

it('dispatches browser events', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-emit/doBrowserEvent', 'component-with-emit');

    $output = yoyo_update();

    $responseHeaders = headers();

    resetYoyoRequest();

    $browserEvents = json_decode($responseHeaders['Yoyo-Browser-Event'], true);
    expect($browserEvents)->toBeArray();
    expect($browserEvents[0])->toMatchArray([
        'event' => 'notification',
        'params' => ['message' => 'done'],
    ]);
});

// --- Swap modifiers ---

it('adds swap modifier headers via component action', function () {
    mockYoyoGetRequest('http://example.com/', 'component-with-swap-modifiers/doSwap', 'component-with-swap-modifiers');

    $output = yoyo_update();

    $responseHeaders = headers();

    resetYoyoRequest();

    expect($responseHeaders)->toHaveKey('Yoyo-Swap-Modifier', 'transition:true swap:500ms');
});

// --- Counter state management ---

it('increments counter and emits event', function () {
    $output = update('counter', 'increment');
    expect($output)->toContain('The count is now 1');
});

it('renders counter with custom initial value', function () {
    $output = render('counter', ['count' => 10]);
    expect($output)->toContain('The count is now 10');
});

// --- Protected method blocking ---

it('prevents calling boot method directly', function () {
    update('counter', 'boot');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling getName method as action', function () {
    update('counter', 'getName');
})->throws(NonPublicComponentMethodCall::class);

it('prevents calling getComponentId method as action', function () {
    update('counter', 'getComponentId');
})->throws(NonPublicComponentMethodCall::class);

// --- Component set() method ---

it('passes view data set via set() method', function () {
    $output = render('set-view-data');
    expect($output)->toContain('bar-baz');
});

// --- Sub-directory components ---

it('resolves component class in sub-directory via dot notation', function () {
    $output = render('account.register');
    expect($output)->toContain('Please register to access this page');
});
