<?php

use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;
use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\BladeViewProvider;
use Clickfwd\Yoyo\ViewProviders\TwigViewProvider;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;
use Clickfwd\Yoyo\Yoyo;

use function Tests\blade;
use function Tests\twig;

class RenderingOwnershipState
{
    public static $hookOutput;
    public static $pending;
    public static $events = [];
}

class RenderingOwnershipComponent extends Component
{
    public $owner = 'parent';
    public $value = 'value';
    public $template = 'owner';
    public $hook = '';
    public $prepareChild = false;
    public $content;

    public function render()
    {
        return $this->content ?? $this->view('render-ownership/'.$this->template);
    }

    public function rendered($view)
    {
        RenderingOwnershipState::$events[] = 'hook';
        if ($this->hook) {
            RenderingOwnershipState::$pending = $view;
            RenderingOwnershipState::$hookOutput = Yoyo::getInstance()->mount('render-owner', ['owner' => 'child', 'value' => 'child'])->render();
        }
        if ($this->hook === 'retarget') {
            return $view->render('render-ownership/alternate', ['value' => 'retargeted']);
        }
        if ($this->hook === 'replace') {
            return '<div>replacement</div>';
        }
        return $view;
    }

    protected function viewVars(): array
    {
        RenderingOwnershipState::$events[] = 'vars';
        if ($this->prepareChild) {
            Yoyo::getInstance()->mount('render-owner', ['owner' => 'prepared-child'])->render();
        }
        return parent::viewVars();
    }

    public function recoverChild(): string
    {
        try {
            Yoyo::getInstance()->mount('render-owner', ['owner' => 'child', 'template' => 'failure'])->render();
        } catch (Throwable $e) {
            return 'caught';
        }
        throw new LogicException('The child did not fail.');
    }

    public function fail(): void
    {
        throw new RuntimeException('render failed');
    }
}

function renderingOwnershipProvider(string $engine): ViewProviderInterface
{
    $provider = match ($engine) {
        'native' => new YoyoViewProvider(new View(__DIR__.'/../app/resources/views/yoyo')),
        'blade' => new BladeViewProvider(blade()),
        'twig' => new TwigViewProvider(twig()),
    };
    Yoyo::getInstance()->registerViewProvider(fn () => $provider);
    Yoyo::registerComponent('render-owner', RenderingOwnershipComponent::class);
    RenderingOwnershipState::$events = [];
    RenderingOwnershipState::$pending = null;
    RenderingOwnershipState::$hookOutput = null;
    return $provider;
}

function renderingOwnershipText(string $html): string
{
    return preg_replace('/\s+/', '', strip_tags($html));
}

it('keeps the parent pending view when its rendered hook renders a child', function ($engine) {
    $provider = renderingOwnershipProvider($engine);
    $html = Yoyo::getInstance()->mount('render-owner', ['hook' => 'child'])->render();

    expect(renderingOwnershipText($html))->toBe('parent:value')
        ->and(renderingOwnershipText(RenderingOwnershipState::$hookOutput))->toBe('child:child')
        ->and(RenderingOwnershipState::$pending)->toBeInstanceOf(get_class($provider));
})->with(['native', 'blade', 'twig']);

it('keeps independent pending views across reverse and repeated casts and fluent retargeting', function ($engine) {
    $provider = renderingOwnershipProvider($engine);
    $provider->startYoyoRendering((object) ['owner' => 'first']);
    $first = $provider->render('render-ownership/owner', ['value' => 'one']);
    $provider->startYoyoRendering((object) ['owner' => 'second']);
    $second = $provider->render('render-ownership/alternate', ['value' => 'two']);

    expect(renderingOwnershipText((string) $second))->toBe('alternate:second:two')
        ->and(renderingOwnershipText((string) $first))->toBe('first:one')
        ->and(renderingOwnershipText((string) $first))->toBe('first:one')
        ->and($first->getProviderInstance())->toBe($provider->getProviderInstance())
        ->and($first->render('render-ownership/alternate', ['value' => 'three']))->toBe($first)
        ->and(renderingOwnershipText((string) $first))->toBe('alternate:first:three')
        ->and(renderingOwnershipText((string) $second))->toBe('alternate:second:two');
})->with(['native', 'blade', 'twig']);

it('allows hooks to retarget or replace their concrete pending view', function ($engine) {
    $provider = renderingOwnershipProvider($engine);
    $html = Yoyo::getInstance()->mount('render-owner', ['hook' => 'retarget'])->render();
    expect(renderingOwnershipText($html))->toBe('alternate:parent:retargeted');

    $html = Yoyo::getInstance()->mount('render-owner', ['hook' => 'replace'])->render();
    expect(renderingOwnershipText($html))->toBe('replacement')
        ->and(renderingOwnershipText((string) $provider->render('render-ownership/plain')))->toBe('plain');
})->with(['native', 'blade', 'twig']);

it('restores the parent for an ordinary partial after a nested component', function ($engine) {
    renderingOwnershipProvider($engine);
    $html = Yoyo::getInstance()->mount('render-owner', ['template' => 'nested'])->render();
    expect(renderingOwnershipText($html))->toBe('parent:beforechild:childparent:partialparent:after');
})->with(['native', 'blade', 'twig']);

it('restores the parent after a caught child failure and renders a succeeding sibling', function ($engine) {
    renderingOwnershipProvider($engine);
    $depth = ob_get_level();
    try {
        $html = Yoyo::getInstance()->mount('render-owner', ['template' => 'recovery'])->render();
        expect(renderingOwnershipText($html))->toBe('parent:beforecaughtparent:partialsibling:siblingparent:after')
            ->and(ob_get_level())->toBe($depth);
    } finally {
        while (ob_get_level() > $depth) {
            ob_end_clean();
        }
    }
})->with(['native', 'blade', 'twig']);

it('keeps the parent binding during recursive variable preparation', function ($engine) {
    renderingOwnershipProvider($engine);
    $html = Yoyo::getInstance()->mount('render-owner', ['prepareChild' => true])->render();
    expect(renderingOwnershipText($html))->toBe('parent:value');
})->with(['native', 'blade', 'twig']);

it('binds string templates and restores context after nested rendering and failure', function ($engine) {
    $provider = renderingOwnershipProvider($engine);
    $content = $engine === 'blade'
        ? '<div>{{ $this->owner }}:before @yoyo("render-owner", ["owner" => "child", "value" => "child"]) @include("render-ownership.owner", ["value" => "partial"]) {{ $this->owner }}:after</div>'
        : '<div>{{ this.owner }}:before {{ yoyo("render-owner", {owner: "child", value: "child"}) }} {% include "render-ownership/owner.twig" with {value: "partial"} %} {{ this.owner }}:after</div>';
    $html = Yoyo::getInstance()->mount('render-owner', ['content' => $content])->render();
    expect(renderingOwnershipText($html))->toBe('parent:beforechild:childparent:partialparent:after');

    $content = $engine === 'blade' ? '{{ $this->fail() }}' : '{{ this.fail() }}';
    expect(fn () => Yoyo::getInstance()->mount('render-owner', ['content' => $content])->render())->toThrow(Throwable::class, 'render failed');
    $raw = $provider->getProviderInstance();
    $plain = $engine === 'blade' ? $raw->render('render-ownership/plain') : $raw->render('render-ownership/plain.twig');
    expect(trim($plain))->toBe('plain')
        ->and(renderingOwnershipText(Yoyo::getInstance()->mount('render-owner', ['owner' => 'next'])->render()))->toBe('next:value');
})->with(['blade', 'twig']);

it('keeps caller buffers and discards native partial output when a template throws', function () {
    $provider = renderingOwnershipProvider('native');
    $depth = ob_get_level();
    ob_start();
    echo 'caller';
    try {
        expect(fn () => Yoyo::getInstance()->mount('render-owner', ['template' => 'failure'])->render())->toThrow(RuntimeException::class, 'render failed');
        expect(ob_get_level())->toBe($depth + 1)
            ->and(ob_get_contents())->toBe('caller');
        expect(trim($provider->getProviderInstance()->render('render-ownership/plain')))->toBe('plain')
            ->and(renderingOwnershipText(Yoyo::getInstance()->mount('render-owner', ['owner' => 'next'])->render()))->toBe('next:value');
    } finally {
        while (ob_get_level() > $depth) {
            ob_end_clean();
        }
    }
});

it('keeps native unsupported string failures from leaking component context', function () {
    $provider = renderingOwnershipProvider('native');
    expect(fn () => Yoyo::getInstance()->mount('render-owner', ['content' => 'unsupported'])->render())->toThrow(Exception::class, 'Views from strings are not supported');
    expect(trim($provider->getProviderInstance()->render('render-ownership/plain')))->toBe('plain');
});

class NoncloneableRenderingProvider extends YoyoViewProvider
{
    private function __clone()
    {
    }
}

it('preserves ordinary rendering for a noncloneable built-in subclass', function () {
    $provider = new NoncloneableRenderingProvider(new View(__DIR__.'/../app/resources/views/yoyo'));
    $provider->startYoyoRendering((object) ['owner' => 'legacy']);
    expect(renderingOwnershipText((string) $provider->render('render-ownership/owner', ['value' => 'one'])))->toBe('legacy:one');
    $provider->startYoyoRendering((object) ['owner' => 'next']);
    expect(renderingOwnershipText((string) $provider->render('render-ownership/owner', ['value' => 'two'])))->toBe('next:two');
});

class IndependentRenderingProvider implements ViewProviderInterface
{
    public $result;

    public function __construct($view)
    {
    }

    private function __clone()
    {
        throw new LogicException('Custom providers cannot be cloned.');
    }

    public function exists($template): bool
    {
        RenderingOwnershipState::$events[] = 'exists';
        return true;
    }

    public function startYoyoRendering($component): void
    {
        RenderingOwnershipState::$events[] = 'start';
    }

    public function stopYoyoRendering(): void
    {
        RenderingOwnershipState::$events[] = 'stop';
    }

    public function render($template, $vars = []): ViewProviderInterface
    {
        RenderingOwnershipState::$events[] = 'render';
        return $this->result ?? $this;
    }

    public function makeFromString($content, $vars = []): string
    {
        throw new RuntimeException('custom string failed');
    }

    public function getProviderInstance()
    {
        return $this;
    }

    public function __toString(): string
    {
        RenderingOwnershipState::$events[] = 'cast';
        return '<div>custom</div>';
    }
}

it('preserves custom provider order without cloning or stopping before the cast', function () {
    $provider = new IndependentRenderingProvider(null);
    renderingOwnershipProvider('native');
    Yoyo::getInstance()->registerViewProvider(fn () => $provider);
    expect(renderingOwnershipText(Yoyo::getInstance()->mount('render-owner')->render()))->toBe('custom')
        ->and(RenderingOwnershipState::$events)->toBe(['exists', 'start', 'vars', 'render', 'hook', 'cast']);
});

it('honors a distinct custom render result', function () {
    $provider = new IndependentRenderingProvider(null);
    $provider->result = new class (null) extends IndependentRenderingProvider {
        public function __toString(): string
        {
            return '<div>distinct</div>';
        }
    };
    renderingOwnershipProvider('native');
    Yoyo::getInstance()->registerViewProvider(fn () => $provider);
    expect(renderingOwnershipText(Yoyo::getInstance()->mount('render-owner')->render()))->toBe('distinct');
});
