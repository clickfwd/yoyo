<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Concerns\BrowserEvents;
use Clickfwd\Yoyo\Exceptions\MissingComponentTemplate;
use Clickfwd\Yoyo\Interfaces\View as ViewInterface;
use Clickfwd\Yoyo\Services\Request;
use Closure;
use ReflectionMethod;

abstract class Component
{
    use BrowserEvents;

    protected $id;

    protected $componentName;

    protected $variables;

    protected $request;

    protected $spinning;

    protected $queryString = [];

    private static $excludePublicMethods = [
        '__construct',
        'spinning',
    ];

    private $attributes;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;

        $this->componentName = $name;

        $this->request = Request::getInstance();
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
        return $this;
    }

    public function beforeRender()
    {
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

    public function getComponentId()
    {
        return $this->id;
    }

    public function parameters($array = [])
    {
        return $this->buildParametersForView($array);
    }

    public function render()
    {
        return $this->view($this->componentName);
    }

    protected function view($template, $vars = []): ViewInterface
    {
        $view = Yoyo::getViewProvider();

        if (! $view->exists($template)) {
            throw new MissingComponentTemplate($template, get_class($this));
        }

        // Make public properties and methods available to views

        $vars = array_merge($this->viewVars(), $vars);

        $view->render($template, $vars);

        return $view;
    }

    public function createViewFromString($content): string
    {
        $view = Yoyo::getViewProvider();

        $html = $view->makeFromString($content, $this->viewVars());

        return $html;
    }

    protected function viewVars(): array
    {
        $vars = [];

        $vars['yoyoId'] = $this->createVariableFromMethod(new ReflectionMethod($this, 'yoyoId'));

        $vars['spinning'] = $this->spinning;

        // Make Yoyo parameters closure available to view

        $vars['parameters'] = $this->createVariableFromMethod(new ReflectionMethod($this, 'buildParametersForView'));

        $properties = ClassHelpers::getPublicVars($this);

        $componentMethods = $this->extractPublicMethods();

        $eventMethods = $this->extractEventMethods();

        return array_merge($vars, $properties, $componentMethods, $eventMethods);
    }

    protected function extractPublicMethods(): array
    {
        $vars = [];

        $class = get_class($this);

        foreach (ClassHelpers::getPublicMethods($this, self::$excludePublicMethods) as $method) {
            $methodName = $method;

            if ($method[0] == '_' && $method[1] !== '_') {
                $methodName = substr($method, 1);
            }

            $vars[$methodName] = $this->createVariableFromMethod(new ReflectionMethod($this, $method));
        }

        return $vars;
    }

    protected function extractEventMethods(): array
    {
        $vars = [];

        // Methods injected through EventsManager trait

        foreach (['emit', 'emitTo', 'emitSelf', 'emitUp'] as $method) {
            $vars[$method] = $this->createVariableFromMethod(new ReflectionMethod($this, $method));
        }

        return $vars;
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

    protected function yoyoId($name = null)
    {
        if ($name) {
            return $this->id.'-'.$name;
        }

        return $this->id;
    }

    protected function buildParametersForView(array $array = []): string
    {
        $output = [];

        $vars = ClassHelpers::getPublicVars($this);

        $vars = array_merge($vars, $array);

        return YoyoHelpers::encode_vars($vars);
    }
}
