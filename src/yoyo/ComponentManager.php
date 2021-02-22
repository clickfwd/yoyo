<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use Clickfwd\Yoyo\Exceptions\FailedToRegisterComponent;
use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;

class ComponentManager
{
    private $id;

    private $name;

    private $request;

    private $component;

    private $resolver;

    private static $dynamicComponents = [];

    private static $anonymousComponents = [];

    public function __construct($request, $spinning)
    {
        $this->request = $request;

        $this->spinning = $spinning;
    }

    public function addComponentResolver($resolver)
    {
        $this->resolver = $resolver;
    }

    public function getDefaultPublicVars()
    {
        return ClassHelpers::getDefaultPublicVars($this->component);
    }

    public function getPublicVars()
    {
        if ($this->isAnonymousComponent()) {
            return $this->request->except(['component', YoyoCompiler::yoprefix('id')]);
        }

        $vars = ClassHelpers::getPublicVars($this->component);

        $vars = array_merge($vars, $this->includeYoyoPrefixedVars());

        return $vars;
    }

    public function includeYoyoPrefixedVars()
    {
        $vars = [];

        foreach ($this->request->all() as $key => $value) {
            if (substr($key, 0, 5) == YoyoCompiler::yoprefix('')) {
                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    public function getQueryString()
    {
        if ($this->isAnonymousComponent()) {
            return $this->request->method() == 'GET'
                    ? array_keys($this->request->except(['component', YoyoCompiler::yoprefix('id')]))
                    : [];
        }

        return $this->component->getQueryString();
    }

    public function getProps()
    {
        return $this->component->getProps();
    }

    public function getListeners()
    {
        return $this->component->getListeners();
    }

    public function process($id, $name, $action, $variables, $attributes): string
    {
        $this->component = $this->makeComponentInstance();

        if ($this->isAnonymousComponent()) {
            return $this->processAnonymousComponent($variables, $attributes);
        }

        return $this->processDynamicComponent($action, $variables, $attributes);
    }

    public function isAnonymousComponent(): bool
    {
        return $this->component instanceof AnonymousComponent;
    }

    public function isDynamicComponent(): bool
    {
        return ! $this->component instanceof AnonymousComponent;
    }

    private function processDynamicComponent($action, $variables = [], $attributes = []): string
    {
        $isEventListenerAction = false;
        
        $class = get_class($this->component);

        $listeners = $this->component->getListeners();

        $this->component->setAction($action);
        
        if (!empty($listeners[$action]) || in_array($action, $listeners)) {
            // If action is an event listener, re-route it to the listener method

            $action = !empty($listeners[$action]) ? $listeners[$action] : $action;
            
            $eventParams = $this->request->get('eventParams', []);

            $isEventListenerAction = true;
        } elseif (! method_exists($this->component, $action)) {
            throw new ComponentMethodNotFound($class, $action);
        }
        
        $excludedActions = ClassHelpers::getPublicMethods(Component::class, ['render']);

        if (in_array($action, $excludedActions) ||
            (! $isEventListenerAction && ClassHelpers::methodIsPrivate($this->component, $action))) {
            throw new NonPublicComponentMethodCall($class, $action);
        }

        $this->component->spinning($this->spinning)->boot($variables, $attributes);

        $hookStack = [
            'initialize' => ['initialize'],
            'mount' => ['mount'],
            'rendering' => ['rendering'],
            'rendered' => ['rendered']
        ];

        $parameters = array_merge($variables, $this->request->all());

        // Build stack of trait lifecycle hooks to run after the component hook of the same name
        foreach (ClassHelpers::classUsesRecursive($this->component) as $trait) {
            foreach (array_keys($hookStack) as $hook) {
                $hookStack[$hook][] = $hook.ClassHelpers::classBasename($trait);
            }
        }

        foreach ($hookStack['initialize'] as $method) {
            if (method_exists($this->component, $method)) {
                Yoyo::container()->call([$this->component, $method], $parameters);
            }
        }

        foreach ($hookStack['mount'] as $method) {
            if (method_exists($this->component, $method)) {
                Yoyo::container()->call([$this->component, $method], $parameters);
            }
        }

        if ($action !== 'render') {
            $parameters = $isEventListenerAction ? $eventParams : $this->parseActionArguments();
            
            $parameterNames = ClassHelpers::getMethodParameterNames($this->component, $action);

            if (count($parameterNames) == count($parameters)) {
                $args = array_combine($parameterNames, $parameters);
            } else {
                throw new \InvalidArgumentException("Incorrect number of parameters passed to [{$this->name}::{$action}]");
            }

            $actionResponse = Yoyo::container()->call([$this->component, $action], $args);

            $type = gettype($actionResponse);

            if ($type !== 'string' && $type !== 'NULL') {
                throw new \Exception("Component [{$class}] action [{$action}] response should be a string, instead was [{$type}]");
            }
        }

        foreach ($hookStack['rendering'] as $method) {
            if (method_exists($this->component, $method)) {
                Yoyo::container()->call([$this->component, $method]);
            }
        }

        $view = $this->component->render();
 
        if (is_null($view)) {
            return '';
        }

        // For string based templates
        if (is_string($view)) {
            $view = $this->component->createViewFromString($view);
        }

        foreach ($hookStack['rendered'] as $method) {
            if (method_exists($this->component, $method)) {
                $view = Yoyo::container()->call([$this->component, $method], ['view' => $view]);
            }
        }

        return $view;
    }

    private function parseActionArguments()
    {
        $args = $this->request->get('actionArgs', []);

        return $args;
    }

    private function processAnonymousComponent($variables = [], $attributes = []): string
    {
        $this->component->spinning($this->spinning)->boot($variables, $attributes);

        $view = (string) $this->component->render();

        return $view;
    }

    public static function registerComponent($name, $class = null)
    {
        if ($class && $name !== $class && ! class_exists($class)) {
            throw new FailedToRegisterComponent($name, $class);
        }

        if (! $class || $name == $class) {
            self::$anonymousComponents[$name] = $name;
        } else {
            self::$dynamicComponents[$name] = $class;
        }
    }

    public static function registerComponents($components)
    {
        foreach ($components as $name => $class) {
            if (is_numeric($name)) {
                $name = $class;
                $class = null;
            }
            self::registerComponent($name, $class);
        }
    }

    public function getComponentInstance()
    {
        return $this->component;
    }

    private function makeComponentInstance()
    {
        if ($instance = $this->resolver->resolveDynamic(self::$dynamicComponents)) {
            return $instance;
        }

        if ($instance = $this->resolver->resolveAnonymous(self::$anonymousComponents)) {
            return $instance;
        }

        throw new ComponentNotFound($this->name);
    }
}
