<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Interfaces\ComponentResolverInterface;
use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;
use Clickfwd\Yoyo\Services\Configuration;

class ComponentResolver implements ComponentResolverInterface
{
    protected $id;

    protected $name;

    protected $variables;

    protected $viewProviders;

    public function __construct($id, $name, $variables, $viewProviders = [])
    {
        $this->id = $id;

        $this->name = $name;

        $this->variables = $variables;

        $this->viewProviders = $viewProviders;
    }

    public function source(): ?string
    {
        return $this->variables[YoyoCompiler::yoprefix('source')] ?? null;
    }

    public function resolveDynamic($registered): ?Component
    {
        if (isset($registered[$this->name])) {
            return new $registered[$this->name]($this->id, $this->name, $this);
        }

        $className = YoyoHelpers::studly($this->name);

        $class = Configuration::get('namespace').$className;

        if (is_subclass_of($class, Component::class)) {
            return new $class($this->id, $this->name, $this);
        }

        return null;
    }

    public function resolveAnonymous($registered): ?Component
    {
        if (isset($registered[$this->name])) {
            return new AnonymousComponent($this->id, $this->name, $this);
        }

        $view = $this->resolveViewProvider();

        if ($view->exists($this->name)) {
            return new AnonymousComponent($this->id, $this->name, $this);
        }

        return null;
    }

    public function resolveViewProvider(): ViewProviderInterface
    {
        return $this->viewProviders['default']();
    }
}
