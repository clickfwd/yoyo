<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\View as ViewInterface;

class YoyoViewProvider implements ViewInterface
{
    private $view;

    private $template;

    private $vars;

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function startYoyoRendering($component): void
    {
        $this->view->startYoyoRendering($component);
    }

    public function stopYoyoRendering(): void
    {
        //
    }

    public function render($template, $vars = []): ViewInterface
    {
        $this->template = $template;

        $this->vars = $vars;

        return $this;
    }

    public function makeFromString($content, $vars = []): string
    {
        return $this->view->makeFromString($content, $vars);
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
        return $this->view->render($this->template, $this->vars);
    }
}
