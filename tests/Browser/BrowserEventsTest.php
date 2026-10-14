<?php

require __DIR__.'/bootstrap.php';

function visitBrowserEventsPage($test)
{
    $page = $test->visit(BASE_URL.'/browser-events')
        ->assertSeeIn('#browser-event-message', 'initial');

    $page->script(<<<'JS'
        window.browserEvents = [];
        window.finishedResponses = 0;
        window.finishedSwaps = 0;
        window.finishedHistory = false;
        window.addEventListener('record-saved', (event) => {
            window.browserEvents.push({
                detail: event.detail,
                message: document.querySelector('#browser-event-message').textContent,
            });
        });
        document.addEventListener('htmx:afterOnLoad', (event) => {
            window.responseStatus = event.detail.xhr.status;
            // Count only after all extension handlers have finished dispatching events.
            setTimeout(() => { window.finishedResponses++; }, 0);
        });
        document.addEventListener('htmx:afterSettle', () => {
            setTimeout(() => { window.finishedSwaps++; }, 0);
        });
        window.addEventListener('popstate', () => {
            setTimeout(() => { window.finishedHistory = true; }, 0);
        });
    JS);

    return $page;
}

it('delivers one browser event per completed response', function ($button, $message, $status) {
    $page = visitBrowserEventsPage($this);

    $page->click($button)
        ->assertScript('window.finishedResponses', 1)
        ->assertScript('window.responseStatus', $status);

    expect($page->script('window.browserEvents'))->toBe([
        ['detail' => ['message' => 'saved', 'id' => 42], 'message' => $message],
    ]);
})->with([
    'updated DOM' => ['#save-event', 'saved', 200],
    'no swap' => ['#save-event-no-swap', 'initial', 200],
    'no content' => ['#save-event-no-render', 'initial', 204],
]);

it('delivers one browser event when the response delays its swap', function () {
    $page = visitBrowserEventsPage($this);

    $page->click('#save-event-delayed')
        ->assertScript('window.finishedResponses', 1)
        ->assertScript('window.finishedSwaps', 1)
        ->assertSeeIn('#browser-event-message', 'saved');

    expect($page->script('window.browserEvents'))->toBe([
        ['detail' => ['message' => 'saved', 'id' => 42], 'message' => 'initial'],
    ]);
});

it('replays the browser event once when restoring a cached history state', function () {
    $page = visitBrowserEventsPage($this);

    $page->click('#event-page-two')
        ->assertScript('window.finishedSwaps', 1)
        ->assertSeeIn('#browser-event-message', 'Page 2');

    $page->click('#event-page-three')
        ->assertScript('window.finishedSwaps', 2)
        ->assertSeeIn('#browser-event-message', 'Page 3');

    $page->back()
        ->assertScript('window.finishedHistory', true)
        ->assertSeeIn('#browser-event-message', 'Page 2');

    expect($page->script('window.browserEvents'))->toBe([
        ['detail' => ['page' => 2], 'message' => 'Page 2'],
        ['detail' => ['page' => 3], 'message' => 'Page 3'],
        ['detail' => ['page' => 2], 'message' => 'Page 2'],
    ]);
});
