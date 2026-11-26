<?php

use Clickfwd\Yoyo\Request;

beforeEach(function () {
    $_REQUEST = [];
    $_SERVER = ['REQUEST_METHOD' => 'GET'];
});

it('reads a key that mock() supplies after an earlier drop() of that key', function () {
    $request = new Request();
    $request->drop('yoyo-id');

    $request->mock(['yoyo-id' => 'b'], ['REQUEST_METHOD' => 'POST']);

    expect($request->get('yoyo-id'))->toBe('b');
});

it('reads a key that set() supplies after reset() cleared an earlier drop()', function () {
    $request = new Request();
    $request->drop('yoyo-id');

    $request->reset();
    $request->set('yoyo-id', 'c');

    expect($request->get('yoyo-id'))->toBe('c');
});

it('still hides a dropped key until the request is replaced', function () {
    $request = new Request();
    $request->set('yoyo-id', 'a');
    $request->drop('yoyo-id');

    expect($request->get('yoyo-id', 'default'))->toBe('default');
});
