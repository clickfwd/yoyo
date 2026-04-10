<?php

require __DIR__.'/bootstrap.php';

it('renders response headers test page', function () {
    $this->visit(BASE_URL.'/response-headers')
        ->assertVisible('#response-headers-test')
        ->assertSeeIn('#rh-message', 'initial')
        ->assertSeeIn('#retarget-content', 'original');
});

it('retargets response to a different element via HX-Retarget', function () {
    // HX-Retarget with outerHTML swap replaces #retarget-receiver with the component response
    $this->visit(BASE_URL.'/response-headers')
        ->assertSeeIn('#retarget-content', 'original')
        ->click('#btn-retarget')
        ->assertSee('retargeted content');
});

it('changes swap strategy to innerHTML via HX-Reswap', function () {
    // Default swap is outerHTML — the component root is replaced entirely.
    // With HX-Reswap: innerHTML, the component root stays and content goes inside it.
    $this->visit(BASE_URL.'/response-headers')
        ->click('#btn-reswap')
        ->assertSeeIn('#response-headers', 'inner-swapped');
});

it('triggers client-side event via HX-Trigger', function () {
    $this->visit(BASE_URL.'/response-headers')
        ->click('#btn-trigger')
        ->assertSeeIn('#event-log', 'custom-event,');
});

it('triggers client-side event after settle via HX-Trigger-After-Settle', function () {
    $this->visit(BASE_URL.'/response-headers')
        ->click('#btn-trigger-settle')
        ->assertSeeIn('#event-log', 'settle-event,');
});

it('executes pushUrl action and re-renders component', function () {
    // URL push is not assertable in browser tests because historyEnabled is false
    // in the test server config. Feature tests verify the HX-Push-Url header is set.
    $this->visit(BASE_URL.'/response-headers')
        ->click('#btn-push-url')
        ->assertSeeIn('#rh-message', 'url-pushed');
});

it('executes replaceUrl action and re-renders component', function () {
    // Same as pushUrl — HX-Replace-Url header verified in feature tests.
    $this->visit(BASE_URL.'/response-headers')
        ->click('#btn-replace-url')
        ->assertSeeIn('#rh-message', 'url-replaced');
});
