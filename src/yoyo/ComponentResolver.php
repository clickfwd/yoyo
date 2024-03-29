<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class ComponentResolver
{
    protected $name = 'default';

    protected $variables;

    protected $registered;

    protected $hints;

    protected $container;

    public function __invoke(ContainerInterface $container, array $registered = [], array $hints = [])
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

    public function resolving($id, $name, $variables)
    {
    }

    public function resolveComponent($id, $name, $variables): ?Component
    {
        $this->resolving($id, $name, $variables);
        
        if ($instance = $this->resolveDynamic($id, $name)) {
            return $instance;
        }

        return $this->resolveAnonymous($id, $name);
    }

    public function resolveDynamic($id, $name): ?Component
    {
        $classNames = [];
        
        $args = ['resolver' => $this, 'id' => $id, 'name' => $name];

        // Check namespaced components
        if (strpos($name, ViewProviderInterface::HINT_PATH_DELIMITER) > 0) {
            [$namespaceAlias, $name] = explode(ViewProviderInterface::HINT_PATH_DELIMITER, $name);
            if (isset($this->hints[$namespaceAlias])) {
                foreach ($this->hints[$namespaceAlias] as $namespaceHint) {
                    $classNames[] = $namespaceHint . '\\' . $this->dotNotationToClass($name);
                }
            }
        }

        $classNames[] = $this->registered[$name] ?? null;

        $configurationNamespaces = (array) \Clickfwd\Yoyo\Services\Configuration::get('namespace');

        foreach ($configurationNamespaces as $namespaceHint) {
            $classNames[] = $namespaceHint . $this->dotNotationToClass($name);
        }

        $classNames = array_filter(array_unique($classNames));

        foreach ($classNames as $className) {
            if (class_exists($className)) {
                break;
            }
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
            $args['name'] = $this->registered[$name] ?? $name;

            return $this->container->make(AnonymousComponent::class, $args);
        }

        $view = $this->resolveViewProvider();

        if ($view->exists($name)) {
            return $this->container->make(AnonymousComponent::class, $args);
        }

        return null;
    }

    public function dotNotationToClass($name)
    {
        return implode('\\', array_map(function ($name) {
            return YoyoHelpers::studly($name);
        }, explode('.', $name)));
    }

    public function resolveViewProvider(): ViewProviderInterface
    {
        return $this->container->get('yoyo.view.'.$this->getName());
    }
}
