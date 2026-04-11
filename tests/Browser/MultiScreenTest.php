<?php

require __DIR__.'/bootstrap.php';

it('renders initial screen with open button', function () {
    $this->visit(BASE_URL.'/multi-screen')
        ->assertVisible('#wizard')
        ->assertVisible('[data-screen="initial"]')
        ->assertSeeIn('[data-info]', 'Ready to begin')
        ->assertVisible('[data-action="open"]');
});

it('transitions to form screen on open', function () {
    $this->visit(BASE_URL.'/multi-screen')
        ->assertVisible('[data-screen="initial"]')
        ->click('[data-action="open"]')
        ->assertVisible('[data-screen="form"]')
        ->assertMissing('[data-screen="initial"]')
        ->assertVisible('input[name="message"]')
        ->assertVisible('[data-action="submit"]')
        ->assertVisible('[data-action="cancel"]');
});

it('transitions to success screen on submit', function () {
    $page = $this->visit(BASE_URL.'/multi-screen');

    $page->click('[data-action="open"]')
        ->assertVisible('[data-screen="form"]')
        ->wait(0.3);

    $page->fill('input[name="message"]', 'Hello World')
        ->click('[data-action="submit"]')
        ->assertVisible('[data-screen="success"]')
        ->assertMissing('[data-screen="form"]')
        ->assertSeeIn('[data-result]', 'Submitted: Hello World');
});

it('returns to initial screen on cancel', function () {
    $page = $this->visit(BASE_URL.'/multi-screen');

    $page->click('[data-action="open"]')
        ->assertVisible('[data-screen="form"]')
        ->wait(0.3);

    $page->click('[data-action="cancel"]')
        ->assertVisible('[data-screen="initial"]')
        ->assertMissing('[data-screen="form"]');
});

it('returns to initial screen from success via reset', function () {
    $page = $this->visit(BASE_URL.'/multi-screen');

    $page->click('[data-action="open"]')
        ->assertVisible('[data-screen="form"]')
        ->wait(0.3);

    $page->fill('input[name="message"]', 'Test')
        ->click('[data-action="submit"]')
        ->assertVisible('[data-screen="success"]')
        ->wait(0.3);

    $page->click('[data-action="reset"]')
        ->assertVisible('[data-screen="initial"]')
        ->assertMissing('[data-screen="success"]');
});
