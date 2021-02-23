<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Interfaces\ComponentResolverInterface;
use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;
use Clickfwd\Yoyo\Services\Configuration;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;

class ComponentResolver implements ComponentResolverInterface
{
    protected $variables;

    protected $registered;

    protected $container;

    public function __construct(ContainerInterface $container, $registered, $variables)
    {
        $this->container = $container;

        $this->registered = $registered;

        $this->variables = $variables;
    }

    public function source(): ?string
    {
        return $this->variables[YoyoCompiler::yoprefix('source')] ?? null;
    }

    public function resolveComponent($id, $name): ?Component
    {
        if ($instance = $this->resolveDynamic($id, $name)) {
            return $instance;
        }

        return $this->resolveAnonymous($id, $name);
    }

    public function resolveDynamic($id, $name): ?Component
    {
        $args = ['resolver' => $this, 'id' => $id, 'name' => $name];

        $registered = $this->registered[$name] ?? null;
        
        if (! $registered) {
            $className = YoyoHelpers::studly($name);

            $class = Configuration::get('namespace').$className;
    
            if (is_subclass_of($class, Component::class)) {
                return $this->container->make($class, $args);
            }
        }

        try {
            return $this->container->make($registered, $args);
        } catch (ContainerExceptionInterface $e) {
            return null;
        }
    }

    public function resolveAnonymous($id, $name): ?Component
    {
        $args = ['resolver' => $this, 'id' => $id, 'name' => $name];

        if ($this->registered[$name] ?? null) {
            return $this->container->make(AnonymousComponent::class, $args);
        }

        $view = $this->resolveViewProvider();

        if ($view->exists($name)) {
            return $this->container->make(AnonymousComponent::class, $args);
        }

        return null;
    }

    public function resolveViewProvider(): ViewProviderInterface
    {
        return $this->container->get('yoyo.view.default');
    }
}
