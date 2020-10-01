<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\View as ViewInterface;

class BladeViewProvider implements ViewInterface
{
    private $view;

    private $template;

    private $vars;

    private $engine;

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function startYoyoRendering($component): void
    {
        $this->engine = $this->view->getContainer()->get('view.engine.resolver')->resolve('blade');

        $this->engine->startYoyoRendering($component);
    }

    public function stopYoyoRendering(): void
    {
        $this->engine->stopYoyoRendering();
    }

    public function render($template, $vars = []): ViewInterface
    {
        $this->template = $template;

        $this->vars = $vars;

        return $this;
    }

    public function makeFromString($content, $vars = []): string
    {
        $view = $this->view->make((new \Clickfwd\Yoyo\Blade\CreateBladeViewFromString)($content));

        return $view->with($vars)->render();
    }

    public function exists($template): bool
    {
        return $this->view->exists($template);
    }

    public function getProviderInstance()
    {
        return $this->view;
    }

    public function __toString()
    {
        $output = (string) $this->view->make($this->template, $this->vars);

        $this->stopYoyoRendering();

        return $output;
    }
}
