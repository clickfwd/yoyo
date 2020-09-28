<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\View as ViewInterface;

class BladeViewProvider implements ViewInterface
{
    private $view;

    private $template;

    private $vars;

    public function __construct($view)
    {
        $this->view = $view;
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
        return (string) $this->view->make($this->template, $this->vars);
    }
}
