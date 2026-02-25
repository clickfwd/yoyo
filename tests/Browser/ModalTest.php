<?php

require __DIR__.'/bootstrap.php';

it('renders without modal visible', function () {
    $this->visit(BASE_URL.'/modal')
        ->assertVisible('#modal-trigger')
        ->assertVisible('[data-action="open"]')
        ->assertNotPresent('[data-modal]');
});

it('opens modal on button click', function () {
    $this->visit(BASE_URL.'/modal')
        ->assertNotPresent('[data-modal]')
        ->click('[data-action="open"]')
        ->assertVisible('[data-modal]')
        ->assertSeeIn('[data-modal-title]', 'Modal Content')
        ->assertSee('This is the modal body.');
});

it('closes modal on close button click', function () {
    $page = $this->visit(BASE_URL.'/modal');

    $page->click('[data-action="open"]')
        ->assertVisible('[data-modal]')
        ->wait(0.3);

    $page->click('[data-action="close"]')
        ->assertNotPresent('[data-modal]');
});

it('can reopen modal after closing', function () {
    $page = $this->visit(BASE_URL.'/modal');

    $page->click('[data-action="open"]')
        ->assertVisible('[data-modal]')
        ->wait(0.3);

    $page->click('[data-action="close"]')
        ->assertNotPresent('[data-modal]')
        ->wait(0.3);

    $page->click('[data-action="open"]')
        ->assertVisible('[data-modal]');
});

it('uses native dialog element', function () {
    $this->visit(BASE_URL.'/modal')
        ->click('[data-action="open"]')
        ->assertSourceHas('<dialog')
        ->assertSourceHas('data-modal');
});
