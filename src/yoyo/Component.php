<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Concerns\BrowserEvents;
use Clickfwd\Yoyo\Concerns\Redirector;
use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\MissingComponentTemplate;
use Clickfwd\Yoyo\Interfaces\View as ViewInterface;
use Clickfwd\Yoyo\Services\Request;
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

    protected $spinning;

    protected $queryString = [];

    protected $listeners = [];

    protected $noResponse = false;

    protected $computedPropertyCache = [];

    private static $excludePublicMethods = [
        '__construct',
        'spinning',
    ];

    private $attributes;

    public function __construct(string $id, string $name)
    {
        $this->yoyo_id = $id;

        $this->componentName = $name;

        $this->request = Request::getInstance();

        $this->response = Response::getInstance();
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

    public function mount()
    {
    }

    public function beforeRender()
    {
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

    public function parameters($array = [])
    {
        return $this->buildParametersForView($array);
    }

    public function callActionWithArguments($action, $args)
    {
        $this->componentAction = $action;

        return call_user_func_array([$this, $action], $args);
    }

    public function render()
    {
        if (! $this->noResponse) {
            return $this->view($this->componentName);
        }

        // No Content
        $this->response->status(204);

        return null;
    }

    public function end()
    {
        $this->noResponse = true;
    }

    protected function view($template, $vars = []): ViewInterface
    {
        $view = Yoyo::getViewProvider();

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
        $view = Yoyo::getViewProvider();

        $view->startYoyoRendering($this);

        $html = $view->makeFromString($content, $this->viewVars());

        $view->endYoyoRendering();

        return $html;
    }

    protected function viewVars(): array
    {
        $vars = [];

        $vars['spinning'] = $this->spinning;

        // Make Yoyo parameters closure available to view

        $vars['parameters'] = $this->createVariableFromMethod(new ReflectionMethod($this, 'buildParametersForView'));

        $properties = ClassHelpers::getPublicVars($this);

        return array_merge($vars, $properties);
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

    protected function buildParametersForView(array $array = []): string
    {
        $output = [];

        $vars = ClassHelpers::getPublicVars($this);

        $vars = array_merge($vars, $array);

        return YoyoHelpers::encode_vars($vars);
    }

    // For Twig compatibility, because computed properties are not resolved through __get

    public function __call(string $name, array $arguments)
    {
        $studlyProperty = YoyoHelpers::studly($name);

        if (method_exists($this, $computedMethodName = 'get'.$studlyProperty.'Property')) {
            if (isset($this->computedPropertyCache[$name])) {
                return $this->computedPropertyCache[$name];
            }

            return $this->computedPropertyCache[$name] = $this->$computedMethodName();
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
}
