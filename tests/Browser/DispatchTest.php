<?php

require __DIR__.'/bootstrap.php';

it('registers listener events in hx-trigger attribute', function () {
    $this->visit(BASE_URL.'/dispatch')
        ->assertSourceHas('yoyo:name="dispatch-listener"')
        ->assertSourceHas('hx-trigger="refresh,post-created,status-changed,simple-refresh"');
});

it('dispatches event with single named param', function () {
    $this->visit(BASE_URL.'/dispatch')
        ->click('#btn-dispatch')
        ->assertSeeIn('#message', 'Post created with ID: 42');
});

it('dispatches event with multiple named params', function () {
    $this->visit(BASE_URL.'/dispatch')
        ->click('#btn-dispatch-multi')
        ->assertSeeIn('#message', 'Status: active, Reason: manual');
});

it('dispatches targeted event via dispatchTo', function () {
    $this->visit(BASE_URL.'/dispatch')
        ->click('#btn-dispatch-to')
        ->assertSeeIn('#message', 'Post created with ID: 7');
});

it('dispatches event without params', function () {
    $this->visit(BASE_URL.'/dispatch')
        ->click('#btn-dispatch-no-params')
        ->assertSeeIn('#message', 'Refreshed without params');
});

it('does not update non-listening bystander component', function () {
    $this->visit(BASE_URL.'/dispatch')
        ->assertSeeIn('#bystander', 'unchanged')
        ->click('#btn-dispatch')
        ->assertSeeIn('#message', 'Post created with ID: 42')
        ->assertSeeIn('#bystander', 'unchanged');
});

it('dispatchTo non-existent component is a silent no-op', function () {
    $page = $this->visit(BASE_URL.'/dispatch');

    $page->click('#btn-dispatch-nonexistent');
    $page->wait(1);

    // Listener should still have empty message — component was not updated
    $page->assertSourceHas('<span id="message"></span>');
});
