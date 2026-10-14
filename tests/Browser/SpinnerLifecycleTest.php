<?php

require __DIR__.'/bootstrap.php';

function spinnerLifecyclePage($test)
{
    $page = $test->visit(BASE_URL.'/spinner-lifecycle')
        ->assertVisible('#parent-skip')
        ->assertVisible('#child-skip');

    $page->script(<<<'JS'
        const parent = document.getElementById('parent');
        parent.appendChild(document.getElementById('child'));
        parent.setAttribute('data-nested', 'true');
        htmx.process(parent);
        window.spinnerStarts = [];
        window.spinnerFinishes = [];
        window.spinnerWrites = [];
        window.spinnerDelayed = [];
        window.spinnerSettles = 0;
        window.spinnerOriginalChild = document.getElementById('child');
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
        new MutationObserver((records) => {
            for (const {target} of records) {
                if (target.id.endsWith('-delayed') && target.classList.contains('loading')) {
                    window.spinnerDelayed.push(target.id);
                }
            }
        }).observe(document.body, {subtree: true, attributes: true, attributeFilter: ['class']});
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
                trigger: event.detail.requestConfig.headers['HX-Trigger'],
                originalChild: document.getElementById('child') === window.spinnerOriginalChild,
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
    expect($page->script('window.spinnerDelayed'))->toBe(['parent-delayed']);
    expect($page->script('window.spinnerStarts[0].parent'))->toBe([
        'busy' => true, 'delayed' => false, 'action' => true, 'idle' => false, 'display' => 'inline-block',
    ]);
    expect($page->script('window.spinnerStarts[0].child.busy'))->toBeFalse();
    expect($page->script('window.spinnerFinishes[0].parent'))->toBe([
        'busy' => false, 'delayed' => false, 'action' => false, 'idle' => true, 'display' => 'none',
    ]);
});

it('discovers plain spinners inserted below a processed wrapper when its component is reprocessed', function () {
    $page = spinnerLifecyclePage($this);
    $page->script(<<<'JS'
        const parent = document.getElementById('parent');
        const wrapper = document.createElement('div');
        parent.appendChild(wrapper);
        htmx.process(wrapper);
        wrapper.innerHTML = '<span id="extra-spinner" yoyo:spinning.attr="data-loading">Extra</span>';
        for (let i = 0; i < 2; i++) {
            parent.setAttribute('data-pass', i);
            htmx.process(parent);
        }
        window.extraSpinnerClears = [];
        const removeAttribute = Element.prototype.removeAttribute;
        Element.prototype.removeAttribute = function(name) {
            if (this.id === 'extra-spinner' && name === 'data-loading') {
                window.extraSpinnerClears.push(this.id);
            }
            return removeAttribute.call(this, name);
        };
    JS);

    $page->click('#parent-skip')->assertScript('window.spinnerFinishes.length', 1);

    expect($page->script('window.spinnerWrites'))->toBe(['parent-spinner', 'extra-spinner']);
    expect($page->script('window.extraSpinnerClears'))->toBe(['extra-spinner']);
    expect($page->script('document.getElementById("extra-spinner").hasAttribute("data-loading")'))->toBeFalse();
});

it('discovers inserted and moved spinner subtrees without retaining detached definitions', function () {
    $page = spinnerLifecyclePage($this);
    $page->script(<<<'JS'
        const subtree = document.createElement('div');
        subtree.id = 'added-subtree';
        subtree.innerHTML = '<span id="extra-spinner" yoyo:spinning.attr="data-loading">Extra</span>';
        document.getElementById('parent').appendChild(subtree);
        htmx.process(subtree);
    JS);

    $page->click('#parent-skip')->assertScript('window.spinnerFinishes.length', 1);
    expect($page->script('window.spinnerWrites'))->toBe(['parent-spinner', 'extra-spinner']);

    $page->script(<<<'JS'
        window.spinnerWrites = [];
        const subtree = document.getElementById('added-subtree');
        document.getElementById('child').appendChild(subtree);
        subtree.setAttribute('data-moved', 'true');
        htmx.process(subtree);
    JS);

    $page->click('#parent-fast')->assertScript('window.spinnerFinishes.length', 2);
    expect($page->script('window.spinnerWrites'))->toBe(['parent-spinner']);
    $page->script('window.spinnerWrites = []');
    $page->click('#child-fast')->assertScript('window.spinnerFinishes.length', 3);
    expect($page->script('window.spinnerWrites'))->toBe(['child-spinner', 'extra-spinner']);

    $page->script(<<<'JS'
        window.spinnerWrites = [];
        document.getElementById('added-subtree').remove();
    JS);
    $page->click('#child-fast')->assertScript('window.spinnerFinishes.length', 4);
    expect($page->script('window.spinnerWrites'))->toBe(['child-spinner']);
});

it('keeps another component loading when a nested request finishes and replaces its root', function () {
    $page = spinnerLifecyclePage($this);
    $page->script(<<<'JS'
        document.getElementById('child-replace').click();
        document.getElementById('parent-skip').click();
    JS);
    $page->assertScript('window.spinnerFinishes.length', 2);

    $first = $page->script('window.spinnerFinishes[0]');
    $pending = $first['trigger'] === 'child-replace' ? 'parent' : 'child';
    expect($first[$pending]['busy'])->toBeTrue();
    expect($page->script('window.spinnerFinishes[1].parent.busy'))->toBeFalse();
    expect($page->script('window.spinnerFinishes[1].child.busy'))->toBeFalse();
});

it('finishes loading before a delayed swap and cancels delayed spinners on a fast request', function () {
    $page = spinnerLifecyclePage($this);
    $page->click('#child-delayed-swap')
        ->assertScript('window.spinnerFinishes.length', 1)
        ->assertScript('window.spinnerFinishes[0].originalChild', true)
        ->assertScript('window.spinnerFinishes[0].child.busy', false)
        ->assertScript('window.spinnerFinishes[0].child.delayed', false)
        ->assertScript('window.spinnerSettles', 1)
        ->assertSeeIn('#child-result', '1');

    expect($page->script('document.getElementById("child") === window.spinnerOriginalChild'))->toBeFalse();
    $page->script('window.spinnerDelayed = []');
    $page->click('#child-fast')->assertScript('window.spinnerFinishes.length', 2);
    expect($page->script(<<<'JS'
        () => new Promise(resolve => setTimeout(() => resolve(window.spinnerDelayed), 250))
    JS))->toBe([]);
});

it('stops an aborted request and keeps its spinner definitions for the next request', function () {
    $page = spinnerLifecyclePage($this);
    $page->script(<<<'JS'
        document.addEventListener('htmx:beforeRequest', function abort(event) {
            document.removeEventListener('htmx:beforeRequest', abort);
            queueMicrotask(() => htmx.trigger(event.detail.elt, 'htmx:abort'));
        });
    JS);
    $page->click('#child-skip')
        ->assertScript('window.spinnerFinishes.length', 1)
        ->assertScript('window.spinnerFinishes[0].status', 0)
        ->assertScript('window.spinnerFinishes[0].child.busy', false);

    $page->click('#child-skip')
        ->assertScript('window.spinnerFinishes.length', 2)
        ->assertScript('window.spinnerStarts[1].child.busy', true)
        ->assertScript('window.spinnerFinishes[1].child.busy', false)
        ->assertScript('window.spinnerFinishes[1].child.delayed', false);
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

    $page->click('#child-replace')
        ->assertScript('window.spinnerFinishes.length', 2)
        ->assertScript('window.spinnerSettles', 2)
        ->assertSeeIn('#child-result', '1');

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
