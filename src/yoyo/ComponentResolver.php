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

    protected $hints;

    protected $container;

    public function __construct(ContainerInterface $container, array $registered  = [], array $hints = [], array $variables = [])
    {
        $this->container = $container;

        $this->registered = $registered;

        $this->hints = $hints;

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
        $className = null;

        $args = ['resolver' => $this, 'id' => $id, 'name' => $name];

        // Check namespaced components
        if (strpos($name, ViewProviderInterface::HINT_PATH_DELIMITER) > 0) {
            [$namespaceAlias, $name] = explode(ViewProviderInterface::HINT_PATH_DELIMITER, $name);
            if (isset($namespaceAlias, $this->hints)) {
                $className = $this->hints[$namespaceAlias].'\\'.YoyoHelpers::studly($name);
            }
        }

        if ( !$className) {
            $className = $this->registered[$name] ?? null;
        }
        
        if (! $className) {
            $name = YoyoHelpers::studly($name);
            $className = Configuration::get('namespace').$name;
        }

        try {
            return $this->container->make($className, $args);
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
