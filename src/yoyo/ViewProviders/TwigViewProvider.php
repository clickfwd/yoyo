<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;

class TwigViewProvider implements ViewProviderInterface
{
    protected $view;

    protected $template;

    protected $vars;

    protected $yoyoComponent;

    public static $twig_template_extension = 'twig';

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function startYoyoRendering($component): void
    {
        $this->yoyoComponent = $component;
    }

    public function stopYoyoRendering(): void
    {
        //
    }

    public function render($template, $vars = []): ViewProviderInterface
    {
        $this->template = $template;

        $this->vars = $vars;

        return $this;
    }

    public function makeFromString($content, $vars = []): string
    {
        $template = $this->view->createTemplate((string) $content);

        return $template->render($vars);
    }

    public function exists($template): bool
    {
        return $this->view->getLoader()->exists($template.'.'.self::$twig_template_extension);
    }

    public function getProviderInstance()
    {
        return $this->view;
    }

    public function __toString()
    {
        $this->vars['this'] = $this->yoyoComponent;

        return (string) $this->view->render($this->template.'.'.self::$twig_template_extension, $this->vars);
    }
}
