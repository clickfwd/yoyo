<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;

class TwigViewProvider extends BaseViewProvider implements ViewProviderInterface
{
    protected $view;

    protected $template;

    protected $vars;

    public static $twig_template_extension = 'twig';

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function normalizeName($template)
    {
        if (strpos($template, ViewProviderInterface::HINT_PATH_DELIMITER) > 0) {
            [$namespace, $name] = explode(ViewProviderInterface::HINT_PATH_DELIMITER, $template);

            return "@{$namespace}/{$name}";
        }

        return $template;
    }

    public function render($template, $vars = []): ViewProviderInterface
    {
        $template = $this->normalizeName($template);
        $pending = $this->pendingRender();
        $pending->template = $template;
        $pending->vars = $vars;

        return $pending;
    }

    public function makeFromString($content, $vars = []): string
    {
        if ($this->hasYoyoComponent) {
            $vars['this'] = $this->yoyoComponent;
        }

        $template = $this->view->createTemplate((string) $content);

        return $template->render($vars);
    }

    public function exists($template): bool
    {
        $template = $this->normalizeName($template);

        return $this->getLoader()->exists($template.'.'.self::$twig_template_extension);
    }

    public function getLoader()
    {
        return $this->view->getLoader();
    }

    public function addNamespace($namespace, $path)
    {
        $this->getLoader()->addPath($path, $namespace);

        return $this;
    }

    public function prependNamespace($namespace, $path)
    {
        $this->getLoader()->prependPath($path, $namespace);

        return $this;
    }

    public function addLocation($location)
    {
        $this->getLoader()->addPath($location);

        return $this;
    }

    public function prependLocation($location)
    {
        $this->getLoader()->prependPath($location);

        return $this;
    }

    public function __call(string $method, array $params)
    {
        return call_user_func_array([$this->view, $method], $params);
    }

    public function __toString()
    {
        $vars = $this->vars;

        if ($this->hasYoyoComponent) {
            $vars['this'] = $this->yoyoComponent;
        }

        return (string) $this->view->render($this->template.'.'.self::$twig_template_extension, $vars);
    }
}
