<?php

use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\ComponentResolver;
use Clickfwd\Yoyo\ViewProviders\BladeViewProvider;
use Clickfwd\Yoyo\Yoyo;

use function Tests\blade;

class BladeLifecycleState
{
    public static $provider;
    public static $observed = [];
    public static $pending;
}

class ScopedLifecycleBladeProvider extends BladeViewProvider
{
    public $events = [];
    public $components = [];

    public function startYoyoRendering($component): void
    {
        $this->events[] = ['start', $this, $component->owner];
        $this->components[] = $component;
        parent::startYoyoRendering($component);
    }

    public function stopYoyoRendering(): void
    {
        $component = end($this->components);
        $this->events[] = ['stop', $this, $component ? $component->owner : null];
        try {
            parent::stopYoyoRendering();
        } finally {
            array_pop($this->components);
        }
    }

    public function engineAfterStart()
    {
        return $this->engine;
    }
}

class InheritedLifecycleBladeProvider extends ScopedLifecycleBladeProvider
{
}

class OwnEngineLifecycleBladeProvider extends BladeViewProvider
{
    public $events = [];
    public $components = [];

    public function startYoyoRendering($component): void
    {
        $this->events[] = ['start', $this, $component->owner];
        $this->components[] = $component;
        $this->engine = $this->view->getContainer()->get('view.engine.resolver')->resolve('blade');
        $this->engine->startYoyoRendering($component);
    }

    public function stopYoyoRendering(): void
    {
        $component = array_pop($this->components);
        $this->events[] = ['stop', $this, $component ? $component->owner : null];
        $this->engine->stopYoyoRendering();
    }
}

class StartOnlyLifecycleBladeProvider extends BladeViewProvider
{
    public $starts = [];
    public $engineAfterStart;

    public function startYoyoRendering($component): void
    {
        $this->starts[] = $this;
        parent::startYoyoRendering($component);
        $this->engineAfterStart = $this->engine;
    }
}

class StopOnlyLifecycleBladeProvider extends BladeViewProvider
{
    public $stops = [];

    public function stopYoyoRendering(): void
    {
        $this->stops[] = $this;
        parent::stopYoyoRendering();
    }
}

class NoncloneableLifecycleBladeProvider extends BladeViewProvider
{
    private function __clone()
    {
    }
}

class PlainLifecycleBladeProvider extends BladeViewProvider
{
}

class BladeLifecycleComponent extends Component
{
    public $owner = 'first';
    public $failure = '';
    public $content;

    public function render()
    {
        return $this->content ?? $this->view('render-ownership/lifecycle');
    }

    protected function viewVars(): array
    {
        $this->observe('vars');
        if ($this->failure === 'vars') {
            throw new RuntimeException('variable preparation failed');
        }
        return parent::viewVars();
    }

    public function rendered($view)
    {
        BladeLifecycleState::$pending = $view;
        $this->observe('rendered');
        return $view;
    }

    public function fail(): void
    {
        throw new RuntimeException('template evaluation failed');
    }

    public function observe(string $stage): string
    {
        $provider = BladeLifecycleState::$provider;
        BladeLifecycleState::$observed[] = [$stage, end($provider->components) === $this];
        if ($stage === 'template' && $stage === $this->failure) {
            throw new RuntimeException('template evaluation failed');
        }
        return $this->owner;
    }
}

function lifecycleBladeProvider(string $class = ScopedLifecycleBladeProvider::class): BladeViewProvider
{
    $provider = new $class(blade());
    BladeLifecycleState::$provider = $provider;
    BladeLifecycleState::$observed = [];
    BladeLifecycleState::$pending = null;
    Yoyo::getInstance()->registerViewProvider(fn () => $provider);
    Yoyo::registerComponent('blade-lifecycle', BladeLifecycleComponent::class);
    return $provider;
}

function lifecycleBladeComponent(string $owner): BladeLifecycleComponent
{
    $component = new BladeLifecycleComponent(new ComponentResolver(), $owner, $owner);
    $component->owner = $owner;
    return $component;
}

function lifecycleBladeEngine(BladeViewProvider $provider)
{
    return $provider->getProviderInstance()->getContainer()->get('view.engine.resolver')->resolve('blade');
}

it('keeps one public hook pair on the original Blade receiver through component rendering', function ($class) {
    $provider = lifecycleBladeProvider($class);
    expect(Yoyo::getInstance()->mount('blade-lifecycle')->render())->toContain('first');
    expect($provider->events)->toBe([['start', $provider, 'first'], ['stop', $provider, 'first']])
        ->and(BladeLifecycleState::$pending)->toBe($provider)
        ->and(BladeLifecycleState::$observed)->toBe([['vars', true], ['rendered', true], ['template', true]])
        ->and($provider->components)->toBe([]);
})->with([ScopedLifecycleBladeProvider::class, InheritedLifecycleBladeProvider::class]);

it('exposes the protected Blade engine already bound after parent start', function () {
    $provider = lifecycleBladeProvider();
    $provider->startYoyoRendering(lifecycleBladeComponent('active'));
    try {
        expect($provider->engineAfterStart())->toBe(lifecycleBladeEngine($provider))
            ->and(trim($provider->getProviderInstance()->render('render-ownership/owner', ['value' => 'bound'])))->toBe('<span>active:bound</span>');
    } finally {
        $provider->stopYoyoRendering();
    }
});

it('closes the original Blade hook scope after template or preparation failure and can render again', function ($failure) {
    $provider = lifecycleBladeProvider();
    $message = $failure === 'vars' ? 'variable preparation failed' : 'template evaluation failed';
    expect(fn () => Yoyo::getInstance()->mount('blade-lifecycle', ['failure' => $failure])->render())->toThrow(Exception::class, $message);
    expect($provider->events)->toBe([['start', $provider, 'first'], ['stop', $provider, 'first']])
        ->and($provider->components)->toBe([])
        ->and(trim($provider->getProviderInstance()->render('render-ownership/plain')))->toBe('plain');
    expect(Yoyo::getInstance()->mount('blade-lifecycle', ['owner' => 'next'])->render())->toContain('next');
    expect($provider->events)->toBe([
        ['start', $provider, 'first'], ['stop', $provider, 'first'],
        ['start', $provider, 'next'], ['stop', $provider, 'next'],
    ]);
})->with(['template', 'vars']);

it('uses exactly one public Blade hook pair for successful and failing string templates', function ($failure) {
    $provider = lifecycleBladeProvider();
    $render = fn () => Yoyo::getInstance()->mount('blade-lifecycle', [
        'content' => '<div>{{ $this->observe("template") }}</div>',
        'failure' => $failure,
    ])->render();
    if ($failure) {
        expect($render)->toThrow(Exception::class, 'template evaluation failed');
    } else {
        expect($render())->toContain('first');
    }
    expect($provider->events)->toBe([['start', $provider, 'first'], ['stop', $provider, 'first']])
        ->and(BladeLifecycleState::$observed)->toBe($failure ? [['vars', true], ['template', true]] : [['vars', true], ['template', true], ['rendered', false]])
        ->and($provider->components)->toBe([])
        ->and(trim($provider->getProviderInstance()->render('render-ownership/plain')))->toBe('plain');
    expect(Yoyo::getInstance()->mount('blade-lifecycle', ['owner' => 'next'])->render())->toContain('next');
    expect($provider->events)->toBe([
        ['start', $provider, 'first'], ['stop', $provider, 'first'],
        ['start', $provider, 'next'], ['stop', $provider, 'next'],
    ]);
})->with(['', 'template']);

it('keeps a Blade provider with only one overridden lifecycle hook on the same receiver', function ($class) {
    $provider = lifecycleBladeProvider($class);
    $provider->startYoyoRendering(lifecycleBladeComponent('first'));
    $pending = $provider->render('render-ownership/owner', ['value' => 'one']);
    expect($pending)->toBe($provider)
        ->and(trim((string) $pending))->toBe('<span>first:one</span>')
        ->and(trim($provider->getProviderInstance()->render('render-ownership/plain')))->toBe('plain');
    if ($provider instanceof StartOnlyLifecycleBladeProvider) {
        expect($provider->starts)->toBe([$provider])
            ->and($provider->engineAfterStart)->toBe(lifecycleBladeEngine($provider));
    } else {
        expect($provider->stops)->toBe([$provider]);
    }
})->with([StartOnlyLifecycleBladeProvider::class, StopOnlyLifecycleBladeProvider::class]);

it('does not stop an outer Blade scope when a legacy render is cast repeatedly', function ($failure) {
    $provider = lifecycleBladeProvider();
    $provider->startYoyoRendering(lifecycleBladeComponent('outer'));
    $provider->startYoyoRendering(lifecycleBladeComponent('inner'));
    $pending = $provider->render('render-ownership/'.($failure ? 'failure' : 'owner'), ['value' => 'value']);
    if ($failure) {
        expect(fn () => (string) $pending)->toThrow(Exception::class, 'template evaluation failed');
    } else {
        expect(trim((string) $pending))->toBe('<span>inner:value</span>');
    }
    $events = [['start', $provider, 'outer'], ['start', $provider, 'inner'], ['stop', $provider, 'inner']];
    expect($provider->events)->toBe($events);
    if ($failure) {
        expect(fn () => (string) $pending)->toThrow(Exception::class, 'template evaluation failed');
    } else {
        (string) $pending;
    }
    expect($provider->events)->toBe($events)
        ->and(trim($provider->getProviderInstance()->render('render-ownership/owner', ['value' => 'restored'])))->toBe('<span>outer:restored</span>');
    $provider->stopYoyoRendering();
    expect(trim($provider->getProviderInstance()->render('render-ownership/plain')))->toBe('plain');
})->with([false, true]);

it('restores a noncloneable Blade provider preparation frame after a nested render', function () {
    $provider = lifecycleBladeProvider(NoncloneableLifecycleBladeProvider::class);
    $provider->startYoyoRendering(lifecycleBladeComponent('outer'));
    $provider->startYoyoRendering(lifecycleBladeComponent('inner'));
    $inner = $provider->render('render-ownership/owner', ['value' => 'one']);
    expect($inner)->toBe($provider)
        ->and(trim((string) $inner))->toBe('<span>inner:one</span>');
    $outer = $provider->render('render-ownership/owner', ['value' => 'two']);
    expect($outer)->toBe($provider)
        ->and(trim((string) $outer))->toBe('<span>outer:two</span>')
        ->and(trim($provider->getProviderInstance()->render('render-ownership/plain')))->toBe('plain');
});

it('keeps concrete independent snapshots for a Blade subtype that inherits both hooks', function () {
    $provider = lifecycleBladeProvider(PlainLifecycleBladeProvider::class);
    $provider->startYoyoRendering(lifecycleBladeComponent('first'));
    $first = $provider->render('render-ownership/owner', ['value' => 'one']);
    $provider->startYoyoRendering(lifecycleBladeComponent('second'));
    $second = $provider->render('render-ownership/owner', ['value' => 'two']);
    expect($first)->toBeInstanceOf(PlainLifecycleBladeProvider::class)
        ->and($first)->not->toBe($provider)
        ->and($second)->not->toBe($first)
        ->and(trim((string) $second))->toBe('<span>second:two</span>')
        ->and(trim((string) $first))->toBe('<span>first:one</span>')
        ->and(trim((string) $first))->toBe('<span>first:one</span>');
});

it('closes Blade hooks that bind their own engine through the component pipeline', function ($failure, $stringTemplate) {
    $provider = lifecycleBladeProvider(OwnEngineLifecycleBladeProvider::class);
    $render = fn () => Yoyo::getInstance()->mount('blade-lifecycle', [
        'content' => $stringTemplate ? '<div>{{ $this->observe("template") }}</div>' : null,
        'failure' => $failure,
    ])->render();
    if ($failure) {
        expect($render)->toThrow(Exception::class, $failure === 'vars' ? 'variable preparation failed' : 'template evaluation failed');
    } else {
        expect($render())->toContain('first');
    }
    expect($provider->events)->toBe([['start', $provider, 'first'], ['stop', $provider, 'first']])
        ->and($provider->components)->toBe([])
        ->and(trim($provider->getProviderInstance()->render('render-ownership/plain')))->toBe('plain');
    if (! $stringTemplate && $failure !== 'vars') {
        expect(BladeLifecycleState::$pending)->toBe($provider)
            ->and(BladeLifecycleState::$observed)->toBe([['vars', true], ['rendered', true], ['template', true]]);
    }
    expect(Yoyo::getInstance()->mount('blade-lifecycle', ['owner' => 'next'])->render())->toContain('next');
    expect($provider->events)->toBe([
        ['start', $provider, 'first'], ['stop', $provider, 'first'],
        ['start', $provider, 'next'], ['stop', $provider, 'next'],
    ])->and($provider->components)->toBe([])
        ->and(trim($provider->getProviderInstance()->render('render-ownership/plain')))->toBe('plain');
})->with([
    'named success' => ['', false],
    'named failure' => ['template', false],
    'preparation failure' => ['vars', false],
    'string success' => ['', true],
    'string failure' => ['template', true],
]);

it('preserves an outer own-engine Blade hook scope after repeated casts', function ($failure) {
    $provider = lifecycleBladeProvider(OwnEngineLifecycleBladeProvider::class);
    $provider->startYoyoRendering(lifecycleBladeComponent('outer'));
    $provider->startYoyoRendering(lifecycleBladeComponent('inner'));
    $pending = $provider->render('render-ownership/'.($failure ? 'failure' : 'owner'), ['value' => 'value']);
    expect($pending)->toBe($provider);
    if ($failure) {
        expect(fn () => (string) $pending)->toThrow(Exception::class, 'template evaluation failed');
        expect(fn () => (string) $pending)->toThrow(Exception::class, 'template evaluation failed');
    } else {
        expect(trim((string) $pending))->toBe('<span>inner:value</span>')
            ->and(trim((string) $pending))->toBe('<span>outer:value</span>');
    }
    expect($provider->events)->toBe([
        ['start', $provider, 'outer'], ['start', $provider, 'inner'], ['stop', $provider, 'inner'],
    ])->and(trim($provider->getProviderInstance()->render('render-ownership/owner', ['value' => 'restored'])))->toBe('<span>outer:restored</span>');
    $provider->stopYoyoRendering();
    expect($provider->components)->toBe([])
        ->and(trim($provider->getProviderInstance()->render('render-ownership/plain')))->toBe('plain');
})->with([false, true]);
