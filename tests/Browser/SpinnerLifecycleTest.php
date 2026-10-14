<?php

require __DIR__.'/bootstrap.php';

function spinnerLifecyclePage($test)
{
    $page = $test->visit(BASE_URL.'/spinner-lifecycle')
        ->assertVisible('#parent-skip')
        ->assertVisible('#child-skip');

    $page->script(<<<'JS'
        window.spinnerStarts = [];
        window.spinnerFinishes = [];
        window.spinnerWrites = [];
        window.spinnerSettles = 0;
        window.spinnerState = (scope) => {
            const get = (suffix) => document.getElementById(scope + '-' + suffix);
            return {
                busy: get('spinner').hasAttribute('data-loading'),
                delayed: get('delayed').classList.contains('loading'),
                action: get('action').classList.contains('loading'),
                idle: get('remove').classList.contains('idle'),
                display: get('display').style.display,
            };
        };
        const setAttribute = Element.prototype.setAttribute;
        Element.prototype.setAttribute = function(name, value) {
            if (name === 'data-loading') window.spinnerWrites.push(this.id);
            return setAttribute.call(this, name, value);
        };
        const completed = new WeakSet();
        document.addEventListener('htmx:beforeRequest', (event) => {
            queueMicrotask(() => window.spinnerStarts.push({
                parent: window.spinnerState('parent'),
                child: window.spinnerState('child'),
            }));
        });
        document.addEventListener('htmx:afterRequest', (event) => {
            const xhr = event.detail.xhr;
            if (completed.has(xhr)) return;
            completed.add(xhr);
            queueMicrotask(() => window.spinnerFinishes.push({
                status: xhr.status,
                parent: window.spinnerState('parent'),
                child: window.spinnerState('child'),
            }));
        });
        document.addEventListener('htmx:afterSettle', () => {
            queueMicrotask(() => window.spinnerSettles++);
        });
    JS);

    return $page;
}

it('applies each spinner once after repeated processing and keeps nested ownership', function () {
    $page = spinnerLifecyclePage($this);
    $page->script(<<<'JS'
        for (let i = 0; i < 3; i++) {
            const button = document.getElementById('parent-skip');
            button.setAttribute('data-pass', i);
            htmx.process(button);
        }
    JS);

    $page->click('#parent-skip')
        ->assertScript('window.spinnerFinishes.length', 1)
        ->assertScript('window.spinnerFinishes[0].status', 204);

    expect($page->script('window.spinnerWrites'))->toBe(['parent-spinner']);
    expect($page->script('window.spinnerStarts[0].parent'))->toBe([
        'busy' => true, 'delayed' => false, 'action' => true, 'idle' => false, 'display' => 'inline-block',
    ]);
    expect($page->script('window.spinnerStarts[0].child.busy'))->toBeFalse();
    expect($page->script('window.spinnerFinishes[0].parent'))->toBe([
        'busy' => false, 'delayed' => false, 'action' => false, 'idle' => true, 'display' => 'none',
    ]);
});

it('shows and clears spinners on consecutive requests without a swap', function (string $button, int $status) {
    $page = spinnerLifecyclePage($this);

    for ($request = 1; $request <= 2; $request++) {
        $page->click('#parent-'.$button)
            ->assertScript('window.spinnerFinishes.length', $request)
            ->assertScript('window.spinnerFinishes['.($request - 1).'].status', $status);

        expect($page->script('window.spinnerStarts['.($request - 1).'].parent.busy'))->toBeTrue();
        expect($page->script('window.spinnerFinishes['.($request - 1).'].parent.busy'))->toBeFalse();
        expect($page->script('window.spinnerFinishes['.($request - 1).'].parent.delayed'))->toBeFalse();
    }
})->with(['204 response' => ['skip', 204], 'no swap response' => ['none', 200]]);

it('preserves active cleanup when a subtree is processed during a request', function () {
    $page = spinnerLifecyclePage($this);
    $page->click('#parent-skip')
        ->assertScript('window.spinnerStarts.length', 1);

    $page->script(<<<'JS'
        const button = document.createElement('button');
        button.setAttribute('hx-get', 'fast');
        document.getElementById('parent').appendChild(button);
        htmx.process(button);
    JS);

    $page->assertScript('window.spinnerFinishes.length', 1)
        ->assertScript('window.spinnerFinishes[0].parent.busy', false)
        ->assertScript('window.spinnerFinishes[0].parent.delayed', false);
});

it('cleans up targeted responses and discovers replacement roots with the same id', function () {
    $page = spinnerLifecyclePage($this);
    $page->click('#parent-partial')
        ->assertScript('window.spinnerFinishes.length', 1)
        ->assertScript('window.spinnerSettles', 1)
        ->assertSeeIn('#parent-result', '1')
        ->assertScript('window.spinnerFinishes[0].parent.busy', false);

    $page->click('#parent-replace')
        ->assertScript('window.spinnerFinishes.length', 2)
        ->assertScript('window.spinnerSettles', 2)
        ->assertSeeIn('#parent-result', '1');

    $page->click('#child-skip')
        ->assertScript('window.spinnerFinishes.length', 3)
        ->assertScript('window.spinnerStarts[2].child.busy', true)
        ->assertScript('window.spinnerStarts[2].parent.busy', false)
        ->assertScript('window.spinnerFinishes[2].child.busy', false);

    $page->click('#parent-fast')
        ->assertScript('window.spinnerFinishes.length', 4)
        ->assertScript('window.spinnerStarts[3].parent.busy', true)
        ->assertScript('window.spinnerFinishes[3].parent.delayed', false);
});
