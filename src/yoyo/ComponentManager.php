<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;

class ComponentManager
{
    private $id;

    private $name;

    private $request;

    private $component;

    private $resolver;

    private $spinning;

    public function __construct($resolver, $request, $spinning)
    {
        $this->request = $request;

        $this->spinning = $spinning;

        $this->resolver = $resolver;
    }

    public function getDefaultPublicVars()
    {
        return ClassHelpers::getDefaultPublicVars($this->component, Component::class);
    }

    public function getPublicVars()
    {
        if ($this->isAnonymousComponent()) {
            return $this->request->except(['component', YoyoCompiler::yoprefix('id')]);
        }

        $vars = ClassHelpers::getPublicVars($this->component, Component::class);

        foreach ($this->component->getDynamicProperties() as $name) {
            $vars[$name] = property_exists($this->component, $name) ? $this->component->{$name} : null;
        }

        $vars = array_merge($vars, $this->request->startsWith(YoyoCompiler::yoprefix('')));

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

    public function process($id, $name, $action, $variables, $attributes)
    {
        if (! ($this->component = $this->resolver->resolveComponent($id, $name, $variables))) {
            throw new ComponentNotFound($name);
        }

        if ($this->isAnonymousComponent()) {
            return $this->processAnonymousComponent($variables, $attributes);
        }

        return $this->processDynamicComponent($action, $variables, $attributes);
    }

    public function isAnonymousComponent(): bool
    {
        return is_a($this->component, AnonymousComponent::class);
    }

    public function isDynamicComponent(): bool
    {
        return ! $this->isAnonymousComponent();
    }

    private function processDynamicComponent($action, $variables = [], $attributes = [])
    {
        $class = get_class($this->component);

        $this->component->setAction($action);

        $isEventListenerAction = false;

        $eventParams = $this->request->get('eventParams', []);
        
        $this->component->spinning($this->spinning)->boot($variables, $attributes);

        $hookStack = [
            'initialize' => ['initialize'],
            'mount' => ['mount'],
            'rendering' => ['rendering'],
            'rendered' => ['rendered'],
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

        $listeners = $this->component->getListeners();

        if (! empty($listeners[$action]) || in_array($action, $listeners)) {
            // If action is an event listener, re-route it to the listener method

            $action = ! empty($listeners[$action]) ? $listeners[$action] : $action;
            
            $isEventListenerAction = true;
        } elseif (! method_exists($this->component, $action)) {
            throw new ComponentMethodNotFound($class, $action);
        }
        
        $excludedActions = ClassHelpers::getPublicMethods(Component::class, ['render']);

        if (in_array($action, $excludedActions) ||
            (! $isEventListenerAction && ClassHelpers::methodIsPrivate($this->component, $action))) {
            throw new NonPublicComponentMethodCall($class, $action);
        }

        foreach ($hookStack['mount'] as $method) {
            if (method_exists($this->component, $method)) {
                Yoyo::container()->call([$this->component, $method], $parameters);
            }
        }

        if (! in_array($action, ['render', 'refresh'])) {
            $parameters = $isEventListenerAction ? $eventParams : $this->parseActionArguments();
            
            // Get parameter information with types
            $paramInfo = ClassHelpers::getMethodParametersWithTypes($this->component, $action);
            $regularParams = $paramInfo['regular'];
            $typedParams = $paramInfo['typed'];
            
            // Extract just the names of regular parameters for backwards compatibility
            $parameterNames = array_column($regularParams, 'name');
            
            // Check if the last regular parameter is variadic
            $hasVariadic = ! empty($regularParams) && end($regularParams)['variadic'];
            
            // Handle variadic parameters
            if ($hasVariadic && count($parameterNames) > 0) {
                $regularParamCount = count($parameterNames) - 1; // Exclude the variadic parameter
                
                if (count($parameters) >= $regularParamCount) {
                    // Split parameters into regular and variadic
                    $regularParamValues = array_slice($parameters, 0, $regularParamCount);
                    $variadicParamValues = array_slice($parameters, $regularParamCount);
                    
                    // Create args array with named regular parameters and indexed variadic parameters
                    $args = [];
                    for ($i = 0; $i < $regularParamCount; $i++) {
                        $args[$parameterNames[$i]] = $regularParamValues[$i] ?? null;
                    }
                    
                    // Add variadic parameters as indexed values (not named)
                    foreach ($variadicParamValues as $value) {
                        $args[] = $value;
                    }
                } else {
                    throw new \InvalidArgumentException("Too few parameters passed to [{$this->name}::{$action}]");
                }
            } else {
                // Check if all regular parameters are optional
                $requiredCount = 0;
                foreach ($regularParams as $param) {
                    if (! $param['optional']) {
                        $requiredCount++;
                    }
                }
                
                // Only validate regular parameters (not typed/DI parameters)
                if (count($parameters) >= $requiredCount && count($parameters) <= count($parameterNames)) {
                    // Parameters count is valid (between required and total)
                    $args = [];
                    for ($i = 0; $i < count($parameterNames); $i++) {
                        $args[$parameterNames[$i]] = $parameters[$i] ?? null;
                    }
                } elseif (empty($parameterNames) && empty($parameters)) {
                    // Method has only typed parameters (or no parameters at all)
                    $args = [];
                } else {
                    throw new \InvalidArgumentException("Incorrect number of parameters passed to [{$this->name}::{$action}]");
                }
            }
            
            // The container will handle dependency injection for typed parameters
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

        return (string) $view;
    }

    private function parseActionArguments()
    {
        $args = $this->request->get('actionArgs', []);

        return $args;
    }

    private function processAnonymousComponent($variables = [], $attributes = [])
    {
        $this->component->spinning($this->spinning)->boot($variables, $attributes);

        $view = (string) $this->component->render();

        return $view;
    }

    public function getComponentInstance()
    {
        return $this->component;
    }
}
