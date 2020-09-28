<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\View as ViewInterface;

class TwigViewProvider implements ViewInterface
{
    private $view;

    private $template;

    private $vars;

    public static $twig_template_extension = 'twig';

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
        return (string) $this->view->render($this->template.'.'.self::$twig_template_extension, $this->vars);
    }
}
