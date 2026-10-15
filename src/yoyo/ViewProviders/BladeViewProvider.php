<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;

class BladeViewProvider extends BaseViewProvider implements ViewProviderInterface
{
    protected $view;

    protected $engine;

    protected $template;

    protected $vars;

    private $legacyRenderPending = false;

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function startYoyoRendering($component): void
    {
        parent::startYoyoRendering($component);

        if ($this->usesLegacyLifecycle()) {
            $this->engine = $this->view->getContainer()->get('view.engine.resolver')->resolve('blade');
            $this->engine->startYoyoRendering($component);
        }
    }

    public function stopYoyoRendering(): void
    {
        $this->legacyRenderPending = false;

        if ($this->usesLegacyLifecycle() && $this->hasYoyoComponent) {
            $this->engine->stopYoyoRendering();
        }

        parent::stopYoyoRendering();
    }

    protected function pendingRender(): static
    {
        if ($this->usesLegacyLifecycle()) {
            $this->legacyRenderPending = $this->hasYoyoComponent;

            return $this;
        }

        return parent::pendingRender();
    }

    private function usesLegacyLifecycle(): bool
    {
        $type = new \ReflectionObject($this);

        return ! $type->isCloneable()
            || $type->getMethod('startYoyoRendering')->getDeclaringClass()->getName() !== self::class
            || $type->getMethod('stopYoyoRendering')->getDeclaringClass()->getName() !== self::class;
    }

    public function render($template, $vars = []): ViewProviderInterface
    {
        $pending = $this->pendingRender();
        $pending->template = $template;
        $pending->vars = $vars;

        return $pending;
    }

    public function makeFromString($content, $vars = []): string
    {
        return $this->withComponentContext(function () use ($content, $vars) {
            $view = $this->view->make((new \Clickfwd\Yoyo\Blade\CreateBladeViewFromString())($this->view, $content));

            return $view->with($vars)->render();
        });
    }

    public function exists($template): bool
    {
        return $this->view->exists($template);
    }

    public function getFinder()
    {
        return $this->view->getFinder();
    }

    public function addNamespace($namespace, $hints)
    {
        $this->getFinder()->addNamespace($namespace, $hints);

        return $this;
    }

    public function prependNamespace($namespace, $hints)
    {
        $this->getFinder()->prependNamespace($namespace, $hints);

        return $this;
    }

    public function addLocation($location)
    {
        $this->getFinder()->addLocation($location);

        return $this;
    }

    public function prependLocation($location)
    {
        $this->getFinder()->prependLocation($location);

        return $this;
    }

    public function __call(string $method, array $params)
    {
        return call_user_func_array([$this->view, $method], $params);
    }

    protected function withComponentContext(callable $render): string
    {
        $component = $this->yoyoComponent;

        if ($this->usesLegacyLifecycle() || ! $this->hasYoyoComponent) {
            return $render();
        }

        $engine = $this->view->getContainer()->get('view.engine.resolver')->resolve('blade');
        $engine->startYoyoRendering($component);

        try {
            return $render();
        } finally {
            $engine->stopYoyoRendering();
        }
    }

    public function __toString()
    {
        $stop = $this->legacyRenderPending;
        $this->legacyRenderPending = false;

        try {
            return $this->withComponentContext(fn () => (string) $this->view->make($this->template, $this->vars));
        } finally {
            if ($stop) {
                $this->stopYoyoRendering();
            }
        }
    }
}
