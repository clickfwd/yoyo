<?php

require __DIR__.'/bootstrap.php';

it('renders with default entries', function () {
    $this->visit(BASE_URL.'/todo-list')
        ->assertVisible('#todo-list')
        ->assertVisible('[data-entries]')
        ->assertSee('Build a framework')
        ->assertSee('Buy groceries')
        ->assertSee('Write tests');
});

it('shows active item count', function () {
    $this->visit(BASE_URL.'/todo-list')
        ->assertVisible('[data-active-count]')
        ->assertSeeIn('[data-active-count]', 'items left');
});

it('adds a new todo item', function () {
    $this->visit(BASE_URL.'/todo-list')
        ->fill('input[name="task"]', 'New browser test task')
        ->keys('input[name="task"]', ['Enter'])
        ->assertSee('New browser test task');
});

it('toggles todo completion', function () {
    $this->visit(BASE_URL.'/todo-list')
        ->click('[data-todo-id="1"] input[type="checkbox"]')
        ->assertChecked('[data-todo-id="1"] input[type="checkbox"]');
});

it('deletes a todo item', function () {
    $this->visit(BASE_URL.'/todo-list')
        ->assertSee('Buy groceries')
        ->click('[data-todo-id="2"] [data-delete]')
        ->assertDontSee('Buy groceries');
});
