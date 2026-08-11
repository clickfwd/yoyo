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

    /**
     * Arguments for one lifecycle hook.
     *
     * The container matches by parameter name before it consults the type, so a request
     * variable sharing a container slot's name would be handed to the component in place
     * of the object it asked for. Request data is therefore withheld from container
     * slots; caller-supplied variables still reach them, which is how a parent passes a
     * model down to a nested component.
     *
     * Resolved per method: a component and each of its trait hooks have their own
     * signatures.
     */
    private function lifecycleParameters($method, array $variables, array $requestParameters)
    {
        $typed = ClassHelpers::getMethodParametersWithTypes($this->component, $method)['typed'];

        foreach ($typed as $parameter) {
            unset($requestParameters[$parameter['name']]);
        }

        return array_merge($variables, $requestParameters);
    }

    private function processDynamicComponent($action, $variables = [], $attributes = [])
    {
        $class = get_class($this->component);

        $this->component->setAction($action);

        $isEventListenerAction = false;

        $eventParams = $this->request->get('eventParams', []);

        // Guard: Request::get() returns raw string when test_json decodes to falsy value ([], {})
        // TODO: Root cause is test_json falsy check in Request::get() — track as separate fix
        if (is_string($eventParams)) {
            $decoded = json_decode($eventParams, true, 32);
            $eventParams = is_array($decoded) ? $decoded : [];
        }

        $this->component->spinning($this->spinning)->boot($variables, $attributes);

        $hookStack = [
            'initialize' => ['initialize'],
            'mount' => ['mount'],
            'rendering' => ['rendering'],
            'rendered' => ['rendered'],
        ];

        $requestParameters = $this->request->all();

        $parameters = array_merge($variables, $requestParameters);

        // Build stack of trait lifecycle hooks to run after the component hook of the same name
        foreach (ClassHelpers::classUsesRecursive($this->component) as $trait) {
            foreach (array_keys($hookStack) as $hook) {
                $hookStack[$hook][] = $hook.ClassHelpers::classBasename($trait);
            }
        }

        foreach ($hookStack['initialize'] as $method) {
            if (method_exists($this->component, $method)) {
                Yoyo::container()->call([$this->component, $method], $this->lifecycleParameters($method, $variables, $requestParameters));
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
                Yoyo::container()->call([$this->component, $method], $this->lifecycleParameters($method, $variables, $requestParameters));
            }
        }

        if (! in_array($action, ['render', 'refresh'])) {
            $parameters = $isEventListenerAction ? $eventParams : $this->parseActionArguments();

            // Empty params must fall through to existing no-params handling in the else branch
            if ($isEventListenerAction && is_array($parameters) && ! empty($parameters) && array_values($parameters) !== $parameters) {
                // Associative array from JS dispatch — validate required params then pass as named args
                $paramInfo = ClassHelpers::getMethodParametersWithTypes($this->component, $action);
                $regularParams = $paramInfo['regular'];

                foreach ($regularParams as $param) {
                    if (! $param['optional'] && ! $param['variadic'] && ! isset($parameters[$param['name']])) {
                        throw new \InvalidArgumentException(
                            "Missing required parameter [{$param['name']}] for [{$this->name}::{$action}]"
                        );
                    }
                }

                $args = $parameters;
            } else {
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

        return is_array($args) ? $args : [$args];
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
