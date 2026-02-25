<?php

require __DIR__.'/bootstrap.php';

it('renders with empty fields', function () {
    $this->visit(BASE_URL.'/form')
        ->assertVisible('#form')
        ->assertVisible('input#name')
        ->assertVisible('input#email')
        ->assertVisible('button[type="submit"]');
});

it('shows validation errors when submitting empty form', function () {
    $this->visit(BASE_URL.'/form')
        ->click('button[type="submit"]')
        ->assertVisible('[data-error="name"]')
        ->assertSee('Name is required')
        ->assertVisible('[data-error="email"]')
        ->assertSee('Email is required');
});

it('shows success message after valid submission', function () {
    $this->visit(BASE_URL.'/form')
        ->fill('input#name', 'John Doe')
        ->fill('input#email', 'john@example.com')
        ->click('button[type="submit"]')
        ->assertVisible('[data-success]')
        ->assertSee('Thank you for registering!');
});

it('replaces form with success state', function () {
    $this->visit(BASE_URL.'/form')
        ->fill('input#name', 'Jane')
        ->fill('input#email', 'jane@test.com')
        ->click('button[type="submit"]')
        ->assertMissing('input#name')
        ->assertMissing('button[type="submit"]')
        ->assertSee('Thank you for registering!');
});
