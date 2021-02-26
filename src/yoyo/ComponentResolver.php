<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;
use Clickfwd\Yoyo\Services\Configuration;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;

class ComponentResolver
{
    protected $name = 'default';

    protected $variables;

    protected $registered;

    protected $hints;

    protected $container;

    public function __invoke(ContainerInterface $container, array $registered  = [], array $hints = [])
    {
        $this->container = $container;

        $this->registered = $registered;

        $this->hints = $hints;

        return $this;
    }

    public function getName()
    {
        return $this->name;
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
            if (isset($this->hints[$namespaceAlias])) {
                $className = $this->hints[$namespaceAlias].'\\'.YoyoHelpers::studly($name);
            }
        }

        if (!$className) {
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
        return $this->container->get('yoyo.view.'.$this->getName());
    }
}
