<?php

require __DIR__.'/bootstrap.php';

it('renders badge and action buttons', function () {
    $this->visit(BASE_URL.'/events')
        ->assertVisible('#events-test')
        ->assertVisible('#badge')
        ->assertSeeIn('#badge [data-count]', '0')
        ->assertVisible('#btn-email')
        ->assertVisible('#btn-add');
});

it('increments badge count when action button emits event', function () {
    $page = $this->visit(BASE_URL.'/events')
        ->assertSeeIn('#badge [data-count]', '0');

    $page->click('#btn-email [data-action="fire"]')
        ->assertSeeIn('#badge [data-count]', '1');
});

it('increments badge count from multiple buttons', function () {
    $page = $this->visit(BASE_URL.'/events')
        ->assertSeeIn('#badge [data-count]', '0');

    $page->click('#btn-email [data-action="fire"]')
        ->assertSeeIn('#badge [data-count]', '1')
        ->wait(0.3);

    $page->click('#btn-add [data-action="fire"]')
        ->assertSeeIn('#badge [data-count]', '2');
});

it('badge has notification listener in hx-trigger', function () {
    $this->visit(BASE_URL.'/events')
        ->assertSourceHas('hx-trigger="refresh,notification"');
});
