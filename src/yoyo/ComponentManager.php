<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use Clickfwd\Yoyo\Exceptions\FailedToRegisterComponent;
use Clickfwd\Yoyo\Exceptions\NonPublicComponentMethodCall;
use Clickfwd\Yoyo\Services\Configuration;
use ReflectionClass;

class ComponentManager
{
    private $request;

    private $component;

    private static $dynamicComponents = [];

    private static $anonymousComponents = [];

    public function __construct($request, $id, $name, $spinning)
    {
        $this->request = $request;

        $this->spinning = $spinning;

        $this->component = self::makeComponentInstance($id, $name);
    }

    public function getPublicPropertyValues()
    {
        if ($this->isAnonymousComponent()) {
            return $this->request->method() == 'GET'
                        ? $this->request->except(['component', YoyoCompiler::yoprefix('id')])
                        : [];
        }

        return ClassHelpers::getPublicVars($this->component);
    }

    public function getDefaultPropertyValues()
    {
        $reflection = new ReflectionClass($this->component);

        return $reflection->getDefaultProperties();
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

    public function getListeners()
    {
        return $this->component->getListeners();
    }

    public function process($action, $variables, $attributes): string
    {
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

        if (! method_exists($this->component, $action)) {
            // If action is an event listener, re-route it to the listener method
            $listeners = $this->component->getListeners();

            if (!empty($listeners[$action])) {
        
                $eventParams = $this->request->input('eventParams', []);

                $isEventListenerAction = true;
        
                $action = $listeners[$action];
            }
            else {
                throw new ComponentMethodNotFound($class, $action);
            }
        }

        $excludedActions = ClassHelpers::getPublicMethodsBaseClass($this->component, ['render']);

        if (in_array($action, $excludedActions) || ClassHelpers::methodIsPrivate($this->component, $action)) {
            throw new NonPublicComponentMethodCall($class, $action);
        }

        $this->component
                ->spinning($this->spinning)
                ->boot($variables, $attributes)
                ->mount();

        if ($action !== 'render') {
            if ($isEventListenerAction) {
                $actionResponse = call_user_func_array(array($this->component, $action), $eventParams);
            } else {
                $actionResponse = $this->component->$action();
            }

            $type = gettype($actionResponse);

            if ($type !== 'string' && $type !== 'NULL') {
                throw new \Exception("Component [{$class}] action [{$action}] response should be a string, instead was [{$type}]");
            }
        }

        $this->component->beforeRender();

        $view = $this->component->render();

        // For string based templates

        if (is_string($view)) {
            return $this->component->createViewFromString($view);
        }

        return $view;
    }

    private function processAnonymousComponent($variables = [], $attributes = []): string
    {
        $this->component
                ->spinning($this->spinning)
                ->boot($variables, $attributes)
                ->mount();

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

    public static function makeComponentInstance($id, $name)
    {
        if (isset(self::$dynamicComponents[$name])) {
            return new self::$dynamicComponents[$name]($id, $name);
        }

        if ($instance = self::discoverDynamicComponent($id, $name)) {
            return $instance;
        }

        if (isset(self::$anonymousComponents[$name])) {
            return new AnonymousComponent($id, $name);
        }

        if ($instance = self::discoverAnomymousComponent($id, $name)) {
            return $instance;
        }

        throw new ComponentNotFound($name);
    }

    public static function discoverDynamicComponent($id, $name)
    {
        $className = YoyoHelpers::studly($name);

        $class = Configuration::get('namespace').$className;

        if (is_subclass_of($class, Component::class)) {
            return new $class($id, $name);
        }

        return null;
    }

    public static function discoverAnomymousComponent($id, $name)
    {
        $view = Yoyo::getViewProvider();

        if ($view->exists($name)) {
            return new AnonymousComponent($id, $name);
        }

        return null;
    }
}
