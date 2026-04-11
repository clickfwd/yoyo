<?php

require __DIR__.'/bootstrap.php';

it('renders first page of results', function () {
    $this->visit(BASE_URL.'/pagination')
        ->assertVisible('#pagination')
        ->assertVisible('[data-results]')
        ->assertSeeIn('[data-page-info]', 'Showing 1 to 3')
        ->assertSeeIn('[data-results]', 'Item 1');
});

it('navigates to page 2', function () {
    $this->visit(BASE_URL.'/pagination')
        ->click('[data-page="2"]')
        ->assertSeeIn('[data-page-info]', 'Showing 4 to 6')
        ->assertSeeIn('[data-results]', 'Item 4');
});

it('navigates to last page', function () {
    $this->visit(BASE_URL.'/pagination')
        ->click('[data-page="4"]')
        ->assertSeeIn('[data-page-info]', 'Showing 10 to 12')
        ->assertSeeIn('[data-results]', 'Item 12');
});

it('updates query string with page number', function () {
    $this->visit(BASE_URL.'/pagination')
        ->click('[data-page="3"]')
        ->assertQueryStringHas('page', '3');
});

it('highlights active page', function () {
    $this->visit(BASE_URL.'/pagination')
        ->assertAttribute('[data-page="1"]', 'class', 'active');
});
