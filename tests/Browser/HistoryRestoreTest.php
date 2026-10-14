<?php

require __DIR__.'/bootstrap.php';

it('restores earlier content after a server-pushed URL with history cache disabled', function () {
    $page = $this->visit(BASE_URL.'/history')
        ->assertSeeIn('#history-page', 'Page 1')
        ->assertScript('htmx.config.historyEnabled', true);

    $page->script(<<<'JS'
        htmx.config.historyCacheSize = 0;
        htmx.config.refreshOnHistoryMiss = false;
        window.historySettled = false;
        document.addEventListener('htmx:afterSettle', function settled(event) {
            if (event.detail.elt.id !== 'history') return;
            document.removeEventListener('htmx:afterSettle', settled);
            setTimeout(() => { window.historySettled = true; }, 0);
        });
    JS);

    $page->assertScript('htmx.config.historyCacheSize', 0)
        ->assertScript('htmx.config.refreshOnHistoryMiss', false);

    $originalUrl = $page->url();
    $historyLength = $page->script('window.history.length');

    $page->click('#history-next')
        ->assertSeeIn('#history-page', 'Page 2')
        ->assertScript('window.historySettled', true)
        ->assertScript('window.location.href', BASE_URL.'/history?page=2');

    expect($page->script('window.history.length'))->toBe($historyLength + 1);

    $page->back()
        ->assertScript('window.location.href', $originalUrl)
        ->assertSeeIn('#history-page', 'Page 1');

    expect($page->script('window.history.length'))->toBe($historyLength + 1);
});
