<?php

require __DIR__.'/bootstrap.php';

it('renders deleteable items', function () {
    $this->visit(BASE_URL.'/skip-render')
        ->assertVisible('#skip-render-test')
        ->assertVisible('#item-1')
        ->assertVisible('#item-2')
        ->assertSeeIn('#item-1 [data-title]', 'First Item')
        ->assertSeeIn('#item-2 [data-title]', 'Second Item');
});

it('keeps component in DOM after skipRender (204)', function () {
    $page = $this->visit(BASE_URL.'/skip-render')
        ->assertSeeIn('#item-1 [data-title]', 'First Item');

    // Click delete — should return 204, no swap
    $page->click('#item-1 [data-action="delete"]')
        ->waitForEvent('networkidle');

    // Component should still be in the DOM (204 = no swap)
    $page->assertVisible('#item-1')
        ->assertSeeIn('#item-1 [data-title]', 'First Item');
});

it('does not affect other components on 204', function () {
    $page = $this->visit(BASE_URL.'/skip-render')
        ->assertSeeIn('#item-2 [data-title]', 'Second Item');

    $page->click('#item-1 [data-action="delete"]')
        ->waitForEvent('networkidle');

    $page->assertVisible('#item-2')
        ->assertSeeIn('#item-2 [data-title]', 'Second Item');
});
