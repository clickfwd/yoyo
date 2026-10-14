<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;

class BladeViewProvider extends BaseViewProvider implements ViewProviderInterface
{
    protected $view;

    protected $template;

    protected $vars;

    public function __construct($view)
    {
        $this->view = $view;
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

        if (! $this->hasYoyoComponent) {
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
        return $this->withComponentContext(fn () => (string) $this->view->make($this->template, $this->vars));
    }
}
