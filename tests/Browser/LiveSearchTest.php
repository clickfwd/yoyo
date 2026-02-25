<?php

require __DIR__.'/bootstrap.php';

it('renders with empty search input', function () {
    $this->visit(BASE_URL.'/live-search')
        ->assertVisible('#live-search')
        ->assertVisible('input[name="q"]')
        ->assertMissing('[data-results]');
});

it('shows matching results when typing', function () {
    $this->visit(BASE_URL.'/live-search')
        ->typeSlowly('input[name="q"]', 'php', 100)
        ->assertVisible('[data-results]')
        ->assertSeeIn('[data-results]', 'PHP Basics')
        ->assertSeeIn('[data-results]', 'PHP Advanced');
});

it('shows no results message for unmatched query', function () {
    $this->visit(BASE_URL.'/live-search')
        ->typeSlowly('input[name="q"]', 'zzzzz', 100)
        ->assertVisible('[data-no-results]')
        ->assertSee('No results found');
});

it('filters results based on query', function () {
    $this->visit(BASE_URL.'/live-search')
        ->typeSlowly('input[name="q"]', 'yoyo', 100)
        ->assertSeeIn('[data-results]', 'Yoyo Components')
        ->assertDontSeeIn('[data-results]', 'PHP Basics');
});
