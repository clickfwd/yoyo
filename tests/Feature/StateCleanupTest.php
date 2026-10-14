<?php

use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\Exceptions\BypassRenderMethod;
use Clickfwd\Yoyo\Services\BrowserEventsService;
use Clickfwd\Yoyo\Services\PageRedirectService;
use Clickfwd\Yoyo\Services\Response;
use Clickfwd\Yoyo\Yoyo;
use Clickfwd\Yoyo\YoyoCompiler;

use function Tests\yoyo_view;

class CleanupStateComponent extends Component
{
    public $rows = [];
    public $payload = [];
    public $count = 1;
    public $zero = 0;
    public $falsy = false;
    public $rootAttributes = '';
    public $mutation = null;

    protected $props = ['payload', 'count', 'zero', 'falsy', null, 1, false, []];
    protected $queryString = ['payload'];

    protected $rendered = false;

    public function mount()
    {
        if ($this->mutation === 'clear') {
            $this->payload = [];
        }
        if ($this->mutation === 'skip') {
            $this->skipRender();
        }
        if ($this->mutation === 'error') {
            throw new RuntimeException('Failed to render state');
        }
    }

    public function render()
    {
        $this->rendered = true;

        if ($this->omitResponse) {
            return parent::render();
        }

        return $this->view('state-cleanup');
    }
}

class CleanupObservedStateComponent extends CleanupStateComponent
{
    public $observer = null;

    public function getListeners()
    {
        if ($this->observer) {
            $this->observer->calls[] = ['listeners', $this->rows];
        }
        if ($this->rendered && $this->mutation === 'scalar') {
            $this->payload['changing'] = '';
        }

        return [];
    }

    public function getProps()
    {
        if ($this->observer) {
            $this->observer->calls[] = ['props', $this->rows];
        }

        return parent::getProps();
    }
}

class CleanupListenerIterator implements IteratorAggregate
{
    private Closure $effect;

    public function __construct(Closure $effect)
    {
        $this->effect = $effect;
    }

    public function getIterator(): Traversable
    {
        ($this->effect)();

        yield 'changed' => 'render';
    }
}

class CleanupStorageComponent extends CleanupStateComponent
{
    public function mount()
    {
        parent::mount();

        if ($this->mutation === 'unset-props') {
            unset($this->props);
        }
        if ($this->mutation === 'unset-listeners') {
            unset($this->listeners);
        }
        if ($this->mutation === 'nonarray-props') {
            $this->props = new ArrayObject(['payload']);
        }
        if (in_array($this->mutation, ['traversable-listeners', 'nonarray-props'], true)) {
            $this->listeners = new CleanupListenerIterator(function () {
                $this->props = ['rows'];
            });
        }
    }

    public function getPropsProperty()
    {
        $this->payload['changing'] = '';

        return ['payload'];
    }

    public function getListenersProperty()
    {
        $this->props = ['rows'];

        return ['changed' => 'render'];
    }
}

class CleanupObservedYoyo extends Yoyo
{
    public $compiledVariables;

    public function compile($componentType, $html, $spinning = null, $variables = [], $listeners = [], $props = [], $cacheHistory = false): string
    {
        $this->compiledVariables = $variables;

        return parent::compile($componentType, $html, $spinning, $variables, $listeners, $props, $cacheHistory);
    }
}

function cleanupStateValues(string $html): array
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    try {
        $document->loadHTML($html);
        $root = (new DOMXPath($document))->query('//*[@hx-vals]')->item(0);

        return $root ? json_decode($root->getAttribute('hx-vals'), true, 512, JSON_THROW_ON_ERROR) : [];
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}

beforeEach(function () {
    yoyo_view();
    Yoyo::registerComponent('cleanup-state', CleanupStateComponent::class);
    Yoyo::registerComponent('cleanup-observed-state', CleanupObservedStateComponent::class);
    Yoyo::registerComponent('cleanup-storage', CleanupStorageComponent::class);
    Yoyo::request()->mock([], []);
    foreach ([BrowserEventsService::class, PageRedirectService::class, Response::class] as $class) {
        (new ReflectionClass($class))->getProperty('instance')->setValue(null, null);
    }
});

afterEach(function () {
    Yoyo::request()->reset();
});

it('renders view data while exposing cleaned declared props', function (bool $refresh) {
    $rows = [['title' => 'Visible row', 'empty' => '', 'nested' => ['nil' => null, 'value' => 7]]];
    $payload = ['empty' => '', 'null' => null, 'emptyArray' => [], 'zero' => 0, 'false' => false, 'nested' => ['empty' => '', 'value' => 7]];
    $instance = Yoyo::getInstance()->mount('cleanup-state', compact('rows', 'payload'), ['id' => 'state']);

    $html = $refresh ? $instance->refresh() : $instance->render();

    expect($html)->toContain('<p>Visible row</p>');
    expect(cleanupStateValues($html))->toBe([
        'yoyo-id' => 'state',
        'payload[]' => ['zero' => 0, 'false' => false, 'nested' => ['value' => 7]],
        'count' => 1,
        'zero' => 0,
        'falsy' => false,
    ]);
    expect($rows[0]['nested'])->toBe(['nil' => null, 'value' => 7]);
    expect($payload['empty'])->toBe('');
    expect(Response::getInstance()->getStatusCode())->toBe(200);
})->with([false, true]);

it('exposes inline props recognized by the HTML parser', function (string $attributes) {
    $html = Yoyo::getInstance()->mount('cleanup-state', [
        'rows' => [['title' => 'Visible row', 'empty' => '']],
        'inputOnly' => ['empty' => '', 'value' => 5],
        'rootAttributes' => $attributes,
    ], ['id' => 'state'])->render();

    expect(cleanupStateValues($html))->toMatchArray([
        'rows[]' => [['title' => 'Visible row']],
        'inputOnly[]' => ['empty' => '', 'value' => 5],
    ]);
})->with(['yoyo:props="rows,inputOnly,missing"', 'YOYO:PROPS="rows,inputOnly,missing"']);

it('retains mounted raw values when an exposed property becomes empty', function () {
    $payload = ['empty' => '', 'nested' => ['null' => null]];
    $html = Yoyo::getInstance()->mount('cleanup-state', ['payload' => $payload, 'mutation' => 'clear'], ['id' => 'state'])->render();

    expect(cleanupStateValues($html)['payload[]'])->toBe($payload);
});

it('passes cleaned reference values to listener and prop getters', function (string $mutation, string $expectedOutcome) {
    $shared = ['empty' => '', 'null' => null, 'kept' => 'shared'];
    $observer = (object) ['calls' => []];
    $rows = [['nested' => &$shared]];
    $instance = Yoyo::getInstance()->mount('cleanup-observed-state', compact('rows', 'observer', 'mutation'), ['id' => 'state']);

    try {
        $instance->render();
        $outcome = 'rendered';
    } catch (BypassRenderMethod $exception) {
        $outcome = 'skipped '.$exception->getCode();
    } catch (RuntimeException $exception) {
        $outcome = 'error '.$exception->getMessage();
    }

    expect($outcome)->toBe($expectedOutcome);
    expect($shared)->toBe(['kept' => 'shared']);
    expect(array_column($observer->calls, 0))->toBe(['listeners', 'listeners', 'props']);
    expect($observer->calls[1][1])->toBe([['nested' => ['kept' => 'shared']]]);
    expect($observer->calls[2][1])->toBe([['nested' => ['kept' => 'shared']]]);
})->with([
    ['render', 'rendered'],
    ['skip', 'skipped 204'],
    ['error', 'error Failed to render state'],
]);

it('preserves scalar references changed by listener getters after cleanup', function () {
    $changing = 'before';
    $html = Yoyo::getInstance()->mount('cleanup-observed-state', [
        'payload' => ['changing' => &$changing, 'kept' => 'value'],
        'mutation' => 'scalar',
    ], ['id' => 'state'])->render();

    expect(cleanupStateValues($html)['payload[]'])->toBe(['changing' => '', 'kept' => 'value']);
    expect($changing)->toBe('');
});

it('passes every cleaned public value through subclass compiler overrides', function () {
    $instance = new CleanupObservedYoyo(Yoyo::container());
    $html = $instance->mount('cleanup-state', [
        'rows' => [['title' => 'Visible row', 'empty' => '']],
    ], ['id' => 'state'])->render();

    expect($instance->compiledVariables)->toBe([
        'rows' => [['title' => 'Visible row']],
        'count' => 1,
        'zero' => 0,
        'falsy' => false,
    ]);
    expect($html)->toContain('<p>Visible row</p>');
});

it('preserves raw empty values in both public compiler entry points', function (bool $directCompiler) {
    $payload = ['null' => null, 'empty' => '', 'array' => [], 'zero' => 0, 'false' => false];
    $html = $directCompiler
        ? (new YoyoCompiler('dynamic', 'state', 'direct', compact('payload'), [], false))->addComponentProps(['payload'])->compile('<div></div>')
        : Yoyo::getInstance()->mount('direct', compact('payload'), ['id' => 'state'])->compile('dynamic', '<div></div>', false, [], [], ['payload']);

    expect(cleanupStateValues($html))->toBe(['yoyo-id' => 'state', 'payload[]' => $payload]);
})->with([false, true]);

it('retains raw query-string values independently of cleaned serialized props', function () {
    Yoyo::request()->mock(['payload' => ['empty' => '', 'kept' => 'value']], [
        'REQUEST_METHOD' => 'GET',
        'HTTP_HX_REQUEST' => true,
        'HTTP_HX_CURRENT_URL' => 'https://example.test/list',
    ]);

    $html = Yoyo::getInstance()->mount('cleanup-state', [], ['id' => 'state'])->refresh();

    expect(cleanupStateValues($html)['payload[]'])->toBe(['kept' => 'value']);
    expect(Response::getInstance()->getHeaders())->toBe([
        'Yoyo-Push' => 'https://example.test/list?payload%5Bempty%5D=&payload%5Bkept%5D=value',
        'Yoyo-Emit' => '[]',
        'Yoyo-Browser-Event' => '[]',
    ]);
});

it('preserves mounted and request source values', function (bool $refresh) {
    if ($refresh) {
        Yoyo::request()->mock(['yoyo:source' => 'request'], ['HTTP_HX_REQUEST' => true]);
    }
    $instance = Yoyo::getInstance()->mount('cleanup-state', ['yoyo:source' => 'mounted'], ['id' => 'state']);
    $html = $refresh ? $instance->refresh() : $instance->render();

    expect(cleanupStateValues($html)['yoyo:source'])->toBe($refresh ? 'request' : 'mounted');
})->with([false, true]);

it('leaves ignored and already compiled roots unchanged', function (string $attributes, array $expected) {
    $html = Yoyo::getInstance()->mount('cleanup-state', ['rootAttributes' => $attributes], ['id' => 'state'])->render();

    expect(cleanupStateValues($html))->toBe($expected);
    expect($html)->not->toContain('yoyo:name="cleanup-state"');
})->with([
    ['yoyo:ignore=""', []],
    ['yoyo:name="child" hx-vals=\'{"saved":1}\'', ['saved' => 1]],
]);

it('reads props after listener storage has selected them', function (string $mutation) {
    $html = Yoyo::getInstance()->mount('cleanup-storage', [
        'mutation' => $mutation,
        'rows' => [['title' => 'Visible row', 'empty' => '']],
        'payload' => ['kept' => 'payload'],
    ], ['id' => 'state'])->render();

    expect(cleanupStateValues($html))->toBe([
        'yoyo-id' => 'state',
        'rows[]' => [['title' => 'Visible row']],
    ]);
    expect($html)->toContain('hx-trigger="refresh,changed"');
})->with(['unset-listeners', 'traversable-listeners', 'nonarray-props']);

it('cleans state before resolving unset props through a magic getter', function () {
    $changing = 'before';
    $html = Yoyo::getInstance()->mount('cleanup-storage', [
        'mutation' => 'unset-props',
        'payload' => ['changing' => &$changing, 'kept' => 'value'],
    ], ['id' => 'state'])->render();

    expect(cleanupStateValues($html)['payload[]'])->toBe(['changing' => '', 'kept' => 'value']);
    expect($changing)->toBe('');
});

it('cleans state before invoking a props property hook', function () {
    eval(<<<'PHP'
        class CleanupHookedPropsComponent extends CleanupStateComponent
        {
            protected $props = ['payload'] {
                get {
                    $this->payload['changing'] = '';

                    return $this->props;
                }
            }
        }
        PHP);
    Yoyo::registerComponent('cleanup-hooked-props', CleanupHookedPropsComponent::class);
    $changing = 'before';
    $html = Yoyo::getInstance()->mount('cleanup-hooked-props', [
        'payload' => ['changing' => &$changing, 'kept' => 'value'],
    ], ['id' => 'state'])->render();

    expect(cleanupStateValues($html)['payload[]'])->toBe(['changing' => '', 'kept' => 'value']);
    expect($changing)->toBe('');
})->skip(! method_exists(ReflectionProperty::class, 'hasHooks'), 'Property hooks require PHP 8.4.');

it('cleans exposed references while discarding unrelated view arrays', function () {
    $shared = ['empty' => '', 'null' => null, 'kept' => 'shared'];
    $html = Yoyo::getInstance()->mount('cleanup-state', [
        'rows' => [['title' => 'Visible row', 'empty' => '']],
        'payload' => ['nested' => &$shared],
    ], ['id' => 'state'])->render();

    expect(cleanupStateValues($html)['payload[]'])->toBe(['nested' => ['kept' => 'shared']]);
    expect($shared)->toBe(['kept' => 'shared']);
    expect($html)->toContain('<p>Visible row</p>');
});
