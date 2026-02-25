<?php

require __DIR__.'/bootstrap.php';

it('renders with initial count of 0', function () {
    $this->visit(BASE_URL.'/counter')
        ->assertVisible('#counter')
        ->assertSeeIn('[data-count]', '0');
});

it('increments count when clicking +', function () {
    $this->visit(BASE_URL.'/counter')
        ->assertSeeIn('[data-count]', '0')
        ->click('[data-action="increment"]')
        ->assertSeeIn('[data-count]', '1');
});

it('decrements count when clicking -', function () {
    $this->visit(BASE_URL.'/counter')
        ->click('[data-action="decrement"]')
        ->assertSeeIn('[data-count]', '-1');
});

it('maintains state across multiple actions', function () {
    $page = $this->visit(BASE_URL.'/counter');

    for ($i = 0; $i < 3; $i++) {
        $page->click('[data-action="increment"]')
            ->assertSeeIn('[data-count]', (string) ($i + 1))
            ->wait(0.3);
    }
});

it('updates query string with count value', function () {
    $this->visit(BASE_URL.'/counter')
        ->click('[data-action="increment"]')
        ->assertQueryStringHas('count', '1');
});
