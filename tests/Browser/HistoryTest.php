<?php

require __DIR__.'/bootstrap.php';

it('adds one history entry when a link action pushes an explicit URL', function () {
    $page = $this->visit(BASE_URL.'/history')
        ->assertSeeIn('#history-page', 'Page 1')
        ->assertScript('htmx.config.historyEnabled', true);

    $originalUrl = $page->url();
    $historyLength = $page->script('window.history.length');

    $page->script(<<<'JS'
        window.historySettled = false;
        document.addEventListener('htmx:afterSettle', function settled(event) {
            if (event.detail.elt.id !== 'history') return;
            document.removeEventListener('htmx:afterSettle', settled);
            // Finish event dispatch, including Yoyo's history handler, before asserting.
            setTimeout(() => { window.historySettled = true; }, 0);
        });
    JS);

    $page->click('#history-next')
        ->assertSeeIn('#history-page', 'Page 2')
        ->assertScript('window.historySettled', true)
        ->assertScript('window.location.href', BASE_URL.'/history?page=2');

    expect($page->script('window.history.length'))->toBe($historyLength + 1);

    $page->back()->assertScript('window.location.href', $originalUrl);
});
