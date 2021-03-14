<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Concerns\BrowserEvents;
use Clickfwd\Yoyo\Concerns\Redirector;
use Clickfwd\Yoyo\Exceptions\BypassRenderMethod;
use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\MissingComponentTemplate;
use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;
use Clickfwd\Yoyo\Services\Response;
use Closure;
use ReflectionMethod;

abstract class Component
{
    use BrowserEvents;
    use Redirector;

    protected $yoyo_id;

    protected $componentName;

    protected $componentAction;

    protected $variables;

    protected $request;

    protected $response;

    protected $spinning;

    protected $queryString = [];

    protected $props = [];

    protected $listeners = [];

    protected $omitResponse = false;

    protected $computedPropertyCache = [];

    protected $attributes;

    protected $resolver;

    protected $viewData = [];

    private static $excludePublicMethods = [
        '__construct',
        'spinning',
    ];

    public function __construct(ComponentResolver $resolver, string $id, string $name)
    {
        $this->yoyo_id = $id;

        $this->componentName = $name;

        $this->request = Yoyo::request();

        $this->response = Response::getInstance();

        $this->resolver = $resolver;
    }

    public function spinning(bool $spinning)
    {
        $this->spinning = $spinning;

        return $this;
    }

    public function boot(array $variables, array $attributes)
    {
        $data = array_merge($variables, $this->request->all());

        $this->variables = $variables;

        $this->attributes = $attributes;

        foreach (ClassHelpers::getPublicProperties($this) as $property) {
            $value = $data[$property] ?? $this->{$property};

            $this->{$property} = $value;
        }

        return $this;
    }

    public function getName()
    {
        return $this->componentName;
    }

    public function getInitialAttributes()
    {
        $attributes = $this->attributes;

        return $attributes;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function getQueryString()
    {
        return $this->queryString;
    }

    public function getProps()
    {
        return $this->props;
    }

    public function setAction($action)
    {
        $this->componentAction = $action;
    }

    public function getListeners()
    {
        $listeners = [];

        foreach ($this->listeners as $key => $value) {
            if (is_numeric($key)) {
                $listeners[$value] = $value;
            } else {
                $listeners[$key] = $value;
            }
        }

        return $listeners;
    }

    public function getComponentId()
    {
        return $this->yoyo_id;
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }

        return $this;
    }

    public function render()
    {
        if ($this->omitResponse) {
            throw new BypassRenderMethod($this->response->getStatusCode());
        }

        return $this->view($this->componentName);
    }

    public function addSwapModifiers($modifier)
    {
        $this->response->header('Yoyo-Swap-Modifier', $modifier);

        return $this;
    }
    
    public function skipRender()
    {
        $this->response->status(204);
        $this->omitResponse = true;

        return $this;
    }

    public function skipRenderAndRemove($modifier = 'swap:1s')
    {
        if ($modifier) {
            $this->addSwapModifiers($modifier);
        }
        
        $this->response->status(200);
        $this->omitResponse = true;

        return $this;
    }

    protected function view($template, $vars = []): ViewProviderInterface
    {
        $view = $this->resolver->resolveViewProvider();

        if (! $view->exists($template)) {
            throw new MissingComponentTemplate($template, get_class($this));
        }

        $view->startYoyoRendering($this);

        // Make public properties and methods available to views

        $vars = array_merge($this->viewVars(), $vars);

        $view->render($template, $vars);

        return $view;
    }

    public function createViewFromString($content): string
    {
        $view = $this->resolver->resolveViewProvider();

        $view->startYoyoRendering($this);

        $html = $view->makeFromString($content, $this->viewVars());

        $view->stopYoyoRendering();

        return $html;
    }

    protected function viewVars(): array
    {
        $vars = [];

        $vars['spinning'] = $this->spinning;

        $properties = ClassHelpers::getPublicVars($this);

        return array_merge($this->viewData, $vars, $properties);
    }

    protected function createVariableFromMethod(ReflectionMethod $method)
    {
        return $method->getNumberOfParameters() === 0
                        ? $this->createInvocableVariable($method->getName())
                        : Closure::fromCallable([$this, $method->getName()]);
    }

    protected function createInvocableVariable(string $method)
    {
        return new InvocableComponentVariable(function () use ($method) {
            return $this->{$method}();
        });
    }

    // For computed properties with arguments
    // For Twig compatibility, because computed properties are not resolved through __get

    public function __call(string $name, array $arguments)
    {
        $studlyProperty = YoyoHelpers::studly($name);
        
        if (method_exists($this, $computedMethodName = 'get'.$studlyProperty.'Property')) {
            $key = static::makeCacheKey($name, $arguments);

            if (isset($this->computedPropertyCache[$key])) {
                return $this->computedPropertyCache[$key];
            }

            return $this->computedPropertyCache[$key] = call_user_func_array([$this,$computedMethodName], $arguments);
        }

        throw new ComponentMethodNotFound($this->getName(), $name);
    }

    public function __get($property)
    {
        $studlyProperty = YoyoHelpers::studly($property);

        if (method_exists($this, $computedMethodName = 'get'.$studlyProperty.'Property')) {
            if (isset($this->computedPropertyCache[$property])) {
                return $this->computedPropertyCache[$property];
            }

            return $this->computedPropertyCache[$property] = $this->$computedMethodName();
        }

        throw new ComponentMethodNotFound($this->getName(), $property);
    }

    public function forgetComputed($key = null)
    {
        if (is_null($key)) {
            $this->computedPropertyCache = [];

            return;
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $keyName) {
            unset($this->computedPropertyCache[$keyName]);
        }
    }

    public function forgetComputedWithArgs($name, ...$args)
    {
        $this->forgetComputed(static::makeCacheKey($name, $args));
    }

    protected static function makeCacheKey($name, $arguments)
    {
        return md5($name.json_encode($arguments));
    }
}
