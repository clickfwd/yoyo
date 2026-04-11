<?php

require __DIR__.'/bootstrap.php';

it('renders all products with their own component instances', function () {
    $this->visit(BASE_URL.'/product-list')
        ->assertVisible('#product-list')
        ->assertVisible('#fav-1')
        ->assertVisible('#fav-2')
        ->assertVisible('#fav-3')
        ->assertVisible('#status-1')
        ->assertVisible('#status-2')
        ->assertVisible('#status-3');
});

it('renders each favorite button with unfavorited state', function () {
    $this->visit(BASE_URL.'/product-list')
        ->assertSeeIn('#fav-1', '☆')
        ->assertSeeIn('#fav-2', '☆')
        ->assertSeeIn('#fav-3', '☆');
});

it('renders each status dropdown with correct initial status', function () {
    $this->visit(BASE_URL.'/product-list')
        ->assertSeeIn('#status-1 [data-status]', 'Active')
        ->assertSeeIn('#status-2 [data-status]', 'Draft')
        ->assertSeeIn('#status-3 [data-status]', 'Archived');
});

it('toggles favorite on one item without affecting others', function () {
    $page = $this->visit(BASE_URL.'/product-list')
        ->assertSeeIn('#fav-1', '☆')
        ->assertSeeIn('#fav-2', '☆');

    $page->click('#fav-1 [data-action="toggle"]')
        ->assertSeeIn('#fav-1', '★')
        ->assertSeeIn('#fav-2', '☆');
});

it('toggles favorite back to unfavorited', function () {
    $page = $this->visit(BASE_URL.'/product-list')
        ->assertSeeIn('#fav-2', '☆');

    $page->click('#fav-2 [data-action="toggle"]')
        ->assertSeeIn('#fav-2', '★')
        ->wait(0.3);

    $page->click('#fav-2 [data-action="toggle"]')
        ->assertSeeIn('#fav-2', '☆');
});

it('can favorite multiple items independently', function () {
    $page = $this->visit(BASE_URL.'/product-list')
        ->assertSeeIn('#fav-1', '☆');

    $page->click('#fav-1 [data-action="toggle"]')
        ->assertSeeIn('#fav-1', '★')
        ->wait(0.3);

    $page->click('#fav-3 [data-action="toggle"]')
        ->assertSeeIn('#fav-3', '★');

    $page->assertSeeIn('#fav-2', '☆');
});

it('opens status dropdown menu', function () {
    $page = $this->visit(BASE_URL.'/product-list');

    $page->assertNotPresent('#status-1 [data-menu]');

    $page->click('#status-1 [data-action="toggle-menu"]')
        ->assertVisible('#status-1 [data-menu]')
        ->assertSeeIn('#status-1 [data-option="active"]', 'Active')
        ->assertSeeIn('#status-1 [data-option="draft"]', 'Draft')
        ->assertSeeIn('#status-1 [data-option="archived"]', 'Archived');
});

it('changes status via dropdown without affecting other items', function () {
    $page = $this->visit(BASE_URL.'/product-list');

    $page->click('#status-1 [data-action="toggle-menu"]')
        ->assertVisible('#status-1 [data-menu]')
        ->wait(0.3);

    $page->click('#status-1 [data-option="draft"]')
        ->assertSeeIn('#status-1 [data-status]', 'Draft')
        ->assertNotPresent('#status-1 [data-menu]')
        ->assertSeeIn('#status-2 [data-status]', 'Draft')
        ->assertSeeIn('#status-3 [data-status]', 'Archived');
});

it('each dropdown operates independently', function () {
    $page = $this->visit(BASE_URL.'/product-list');

    $page->click('#status-3 [data-action="toggle-menu"]')
        ->assertVisible('#status-3 [data-menu]')
        ->wait(0.3);

    $page->click('#status-3 [data-option="active"]')
        ->assertSeeIn('#status-3 [data-status]', 'Active');

    $page->assertSeeIn('#status-1 [data-status]', 'Active')
        ->assertSeeIn('#status-2 [data-status]', 'Draft');
});

it('each component has unique yoyo IDs in the DOM', function () {
    $this->visit(BASE_URL.'/product-list')
        ->assertSourceHas('id="fav-1"')
        ->assertSourceHas('id="fav-2"')
        ->assertSourceHas('id="fav-3"')
        ->assertSourceHas('id="status-1"')
        ->assertSourceHas('id="status-2"')
        ->assertSourceHas('id="status-3"')
        ->assertSourceHas('yoyo:name="favorite-button"')
        ->assertSourceHas('yoyo:name="status-dropdown"');
});
