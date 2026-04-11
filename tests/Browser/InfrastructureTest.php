<?php

require __DIR__.'/bootstrap.php';

it('includes htmx script', function () {
    $this->visit(BASE_URL.'/counter')
        ->assertSourceHas('htmx');
});

it('includes yoyo.js script', function () {
    $this->visit(BASE_URL.'/counter')
        ->assertSourceHas('yoyo.js');
});

it('initializes Yoyo configuration in JavaScript', function () {
    $this->visit(BASE_URL.'/counter')
        ->assertSourceHas('Yoyo.url')
        ->assertSourceHas('Yoyo.config(');
});

it('includes yoyo spinning CSS', function () {
    $this->visit(BASE_URL.'/counter')
        ->assertSourceHas('yoyo\\:spinning');
});

it('compiles yoyo: attributes to hx- attributes', function () {
    $this->visit(BASE_URL.'/counter')
        ->assertSourceHas('hx-get="increment"')
        ->assertSourceHas('hx-get="decrement"');
});

it('adds yoyo wrapper attributes to component root', function () {
    $this->visit(BASE_URL.'/counter')
        ->assertSourceHas('yoyo:name="counter"')
        ->assertSourceHas('hx-ext="yoyo"')
        ->assertSourceHas('hx-include="this"');
});
