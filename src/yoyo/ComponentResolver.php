<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Interfaces\ComponentResolverInterface;
use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;
use Clickfwd\Yoyo\Services\Configuration;
use Psr\Container\ContainerInterface;

class ComponentResolver implements ComponentResolverInterface
{
    protected $id;

    protected $name;

    protected $variables;

    protected $container;

    public function __construct($id, $name, $variables, ContainerInterface $container)
    {
        $this->id = $id;

        $this->name = $name;

        $this->variables = $variables;

        $this->container = $container;
    }

    public function source(): ?string
    {
        return $this->variables[YoyoCompiler::yoprefix('source')] ?? null;
    }

    public function resolveDynamic($registered): ?Component
    {
        $args = ['resolver' => $this, 'id' => $this->id, 'name' => $this->name];

        if (isset($registered[$this->name])) {
            return $this->container->make($registered[$this->name], $args);
        }

        $className = YoyoHelpers::studly($this->name);

        $class = Configuration::get('namespace').$className;

        if (is_subclass_of($class, Component::class)) {
            return $this->container->make($class, $args);
        }

        return null;
    }

    public function resolveAnonymous($registered): ?Component
    {
        $args = ['resolver' => $this, 'id' => $this->id, 'name' => $this->name];

        if (isset($registered[$this->name])) {
            return $this->container->make(AnonymousComponent::class, $args);
        }

        $view = $this->resolveViewProvider();

        if ($view->exists($this->name)) {
            return $this->container->make(AnonymousComponent::class, $args);
        }

        return null;
    }

    public function resolveViewProvider(): ViewProviderInterface
    {
        return Yoyo::container()->get('yoyo.view.default');
    }
}
