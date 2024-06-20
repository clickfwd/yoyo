<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;

class PhalconViewProvider extends BaseViewProvider implements ViewProviderInterface
{
    protected $view;

    protected $template;

    protected $vars;

    private $viewExtention = '.phtml';

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function exists($view): bool
    {
        return file_exists($this->view->getViewsDir() . $view . $this->viewExtention);
    }

    public function render($template, $vars = []): ViewProviderInterface
    {
        $this->template = $template;
        $this->vars = $vars;

        return $this;
    }

    public function setViewExtention($viewExtention)
    {
        $this->viewExtention = $viewExtention;

        return $this;
    }

    public function makeFromString($content, $vars = []): string
    {
        $this->view->start();
        $this->view->setContent($content);
        $this->view->setVars($vars);
        $this->view->finish();
        return $this->view->render();
    }

    public function startYoyoRendering($component): void
    {
    }

    public function stopYoyoRendering(): void
    {
    }

    public function __toString()
    {
        return $this->view->render($this->template, $this->vars);
    }
}
