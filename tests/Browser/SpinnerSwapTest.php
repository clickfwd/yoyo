<?php

require __DIR__.'/bootstrap.php';

function spinnerSwapPage($test)
{
    $page = $test->visit(BASE_URL.'/spinner-swap')
        ->assertSeeIn('#spinner-swap-state', 'Unsaved');

    $page->script(<<<'JS'
        window.spinnerSwapButton = document.getElementById('spinner-swap-submit');
        window.spinnerSwapClass = document.getElementById('spinner-swap-class');
        window.spinnerSwapDisplay = document.getElementById('spinner-swap-display');
        window.spinnerSwapStarts = [];
        window.spinnerSwapComplete = false;
        window.spinnerSwapClears = 0;
        const removeAttribute = window.spinnerSwapButton.removeAttribute;
        window.spinnerSwapButton.removeAttribute = function(name) {
            if (name === 'disabled') window.spinnerSwapClears++;
            return removeAttribute.call(this, name);
        };
        window.spinnerSwapState = () => ({
            disabled: window.spinnerSwapButton.disabled,
            highlighted: window.spinnerSwapClass.classList.contains('highlighted'),
            display: window.spinnerSwapDisplay.style.display,
        });
        document.addEventListener('htmx:beforeRequest', () => {
            queueMicrotask(() => window.spinnerSwapStarts.push(window.spinnerSwapState()));
        });
        document.addEventListener('htmx:afterRequest', (event) => {
            window.spinnerSwapRequest = event.detail;
            queueMicrotask(() => window.spinnerSwapComplete = true);
        });
    JS);

    return $page;
}

it('preserves server state on reused morphdom indicators after request cleanup', function (string $state, $initial, $saved) {
    $page = spinnerSwapPage($this);
    expect($page->script('window.spinnerSwapState().'.$state))->toBe($initial);

    $page->click('#spinner-swap-submit')
        ->assertSeeIn('#spinner-swap-state', 'Saved')
        ->assertScript('window.spinnerSwapComplete', true);

    expect($page->script('window.spinnerSwapStarts'))->toBe([
        ['disabled' => true, 'highlighted' => true, 'display' => 'inline-block'],
    ]);
    expect($page->script('window.spinnerSwapRequest.xhr.status'))->toBe(200);
    expect($page->script('document.getElementById("spinner-swap-submit") === window.spinnerSwapButton'))->toBeTrue();
    expect($page->script('document.getElementById("spinner-swap-class") === window.spinnerSwapClass'))->toBeTrue();
    expect($page->script('document.getElementById("spinner-swap-display") === window.spinnerSwapDisplay'))->toBeTrue();
    expect($page->script('window.spinnerSwapState().'.$state))->toBe($saved);

    $page->script(<<<'JS'
        for (let i = 0; i < 2; i++) {
            htmx.trigger(document.getElementById('spinner-swap-form'), 'htmx:afterRequest', window.spinnerSwapRequest);
        }
    JS);

    expect($page->script('window.spinnerSwapClears'))->toBe(1);
    expect($page->script('window.spinnerSwapState().'.$state))->toBe($saved);
})->with([
    'disabled attribute' => ['disabled', false, true],
    'class' => ['highlighted', false, true],
    'display' => ['display', 'none', 'block'],
]);

it('clears originating indicators before an external target handles its response', function () {
    $page = spinnerSwapPage($this);
    $page->script(<<<'JS'
        document.getElementById('spinner-swap-external').addEventListener('htmx:beforeSwap', (event) => {
            window.spinnerSwapExternalState = window.spinnerSwapState();
            event.stopPropagation();
        });
    JS);

    expect($page->script('document.getElementById("spinner-swap-external").closest("[hx-ext]")'))->toBeNull();
    $page->click('#spinner-swap-target')
        ->assertSeeIn('#spinner-swap-external', 'Saved')
        ->assertScript('window.spinnerSwapComplete', true);

    expect($page->script('window.spinnerSwapStarts'))->toBe([
        ['disabled' => true, 'highlighted' => true, 'display' => 'inline-block'],
    ]);
    expect($page->script('window.spinnerSwapRequest.xhr.status'))->toBe(200);
    expect($page->script('window.spinnerSwapExternalState'))->toBe([
        'disabled' => false, 'highlighted' => false, 'display' => 'none',
    ]);
    expect($page->script('window.spinnerSwapClears'))->toBe(1);
});
