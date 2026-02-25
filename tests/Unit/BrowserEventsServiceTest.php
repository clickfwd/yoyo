<?php

use Clickfwd\Yoyo\Services\BrowserEventsService;
use Clickfwd\Yoyo\Services\Response;
use Clickfwd\Yoyo\Yoyo;

uses()->group('browser-events');

beforeEach(function () {
    // Reset singleton instances for clean state
    $ref = new ReflectionClass(BrowserEventsService::class);
    $prop = $ref->getProperty('instance');
    $prop->setAccessible(true);
    $prop->setValue(null, null);

    $ref = new ReflectionClass(Response::class);
    $prop = $ref->getProperty('instance');
    $prop->setAccessible(true);
    $prop->setValue(null, null);

    Yoyo::request()->mock([], [
        'REQUEST_METHOD' => 'GET',
        'HTTP_HX_REQUEST' => true,
    ]);
});

afterEach(function () {
    Yoyo::request()->reset();
});

it('emits an event with params', function () {
    $service = BrowserEventsService::getInstance();
    $service->emit('testEvent', ['key' => 'value']);
    $service->dispatch();

    $headers = Response::getInstance()->getHeaders();
    $events = json_decode($headers['Yoyo-Emit'], true);

    expect($events)->toHaveCount(1);
    expect($events[0]['event'])->toBe('testEvent');
    expect($events[0]['params'])->toBe(['key' => 'value']);
});

it('emits targeted event with emitTo', function () {
    $service = BrowserEventsService::getInstance();
    $service->emitTo('target-component', 'updateEvent', ['id' => 42]);
    $service->dispatch();

    $headers = Response::getInstance()->getHeaders();
    $events = json_decode($headers['Yoyo-Emit'], true);

    expect($events)->toHaveCount(1);
    expect($events[0])->toMatchArray([
        'event' => 'updateEvent',
        'component' => 'target-component',
    ]);
});

it('emits to selector with emitToWithSelector', function () {
    $service = BrowserEventsService::getInstance();
    $service->emitToWithSelector('#my-element', 'selectorEvent', ['data' => true]);
    $service->dispatch();

    $headers = Response::getInstance()->getHeaders();
    $events = json_decode($headers['Yoyo-Emit'], true);

    expect($events)->toHaveCount(1);
    expect($events[0])->toMatchArray([
        'event' => 'selectorEvent',
        'selector' => '#my-element',
    ]);
});

it('emits self-targeted event', function () {
    Yoyo::request()->mock([
        'component' => 'my-component/action',
    ], [
        'REQUEST_METHOD' => 'GET',
        'HTTP_HX_REQUEST' => true,
    ]);

    $service = BrowserEventsService::getInstance();
    $service->emitSelf('selfEvent', ['status' => 'ok']);
    $service->dispatch();

    $headers = Response::getInstance()->getHeaders();
    $events = json_decode($headers['Yoyo-Emit'], true);

    expect($events)->toHaveCount(1);
    expect($events[0])->toMatchArray([
        'event' => 'selfEvent',
        'component' => 'my-component',
        'propagation' => 'self',
    ]);
});

it('does not emit self when no component in request', function () {
    Yoyo::request()->mock([], [
        'REQUEST_METHOD' => 'GET',
        'HTTP_HX_REQUEST' => true,
    ]);

    $service = BrowserEventsService::getInstance();
    $service->emitSelf('selfEvent', ['status' => 'ok']);
    $service->dispatch();

    $headers = Response::getInstance()->getHeaders();
    $events = json_decode($headers['Yoyo-Emit'], true);

    expect($events)->toHaveCount(0);
});

it('dispatches browser event', function () {
    $service = BrowserEventsService::getInstance();
    $service->dispatchBrowserEvent('show-modal', ['title' => 'Confirm']);
    $service->dispatch();

    $headers = Response::getInstance()->getHeaders();
    $browserEvents = json_decode($headers['Yoyo-Browser-Event'], true);

    expect($browserEvents)->toHaveCount(1);
    expect($browserEvents[0])->toMatchArray([
        'event' => 'show-modal',
        'params' => ['title' => 'Confirm'],
    ]);
});

it('queues multiple events', function () {
    $service = BrowserEventsService::getInstance();
    $service->emit('event1', ['a' => 1]);
    $service->emit('event2', ['b' => 2]);
    $service->emit('event3', ['c' => 3]);
    $service->dispatch();

    $headers = Response::getInstance()->getHeaders();
    $events = json_decode($headers['Yoyo-Emit'], true);

    expect($events)->toHaveCount(3);
    expect($events[0]['event'])->toBe('event1');
    expect($events[1]['event'])->toBe('event2');
    expect($events[2]['event'])->toBe('event3');
});

it('queues multiple browser events', function () {
    $service = BrowserEventsService::getInstance();
    $service->dispatchBrowserEvent('toast', ['msg' => 'saved']);
    $service->dispatchBrowserEvent('scroll', ['to' => 'top']);
    $service->dispatch();

    $headers = Response::getInstance()->getHeaders();
    $browserEvents = json_decode($headers['Yoyo-Browser-Event'], true);

    expect($browserEvents)->toHaveCount(2);
});

it('dispatches empty arrays when no events queued', function () {
    $service = BrowserEventsService::getInstance();
    $service->dispatch();

    $headers = Response::getInstance()->getHeaders();

    expect(json_decode($headers['Yoyo-Emit'], true))->toBe([]);
    expect(json_decode($headers['Yoyo-Browser-Event'], true))->toBe([]);
});
