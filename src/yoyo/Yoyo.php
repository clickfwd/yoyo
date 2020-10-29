<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Exceptions\IncompleteComponentParamInRequest;
use Clickfwd\Yoyo\Interfaces\ComponentResolverInterface;
use Clickfwd\Yoyo\Interfaces\RequestInterface;
use Clickfwd\Yoyo\Services\BrowserEventsService;
use Clickfwd\Yoyo\Services\Configuration;
use Clickfwd\Yoyo\Services\PageRedirectService;
use Clickfwd\Yoyo\Services\Response;
use Clickfwd\Yoyo\Services\UrlStateManagerService;

class Yoyo
{
    private $action;

    private $attributes = [];

    private static $componentResolver;

    private $id;

    private $name;

    private static $request;

    private $variables = [];

    private static $viewProviders = [];

    private static $classBindings = [];

    private static $classSingletons = [];

    public function bindRequest(RequestInterface $request)
    {
        self::$request = $request;
    }

    public static function request()
    {
        if (! self::$request) {
            self::$request = new Request();
        }

        return self::$request;
    }

    public function configure($options): void
    {
        Configuration::getInstance($options);
    }

    public function getComponentId($attributes): string
    {
        if (isset($attributes['id'])) {
            $id = $attributes['id'];
        } else {
            $id = self::request()->get(YoyoCompiler::yoprefix_value('id'), YoyoCompiler::yoprefix_value(YoyoHelpers::randString()));
        }

        // Remove the component ID from the request so it's not passed to child components
        self::request()->drop(YoyoCompiler::yoprefix_value('id'));

        return $id;
    }

    private function getComponentResolver()
    {
        $resolverName = $this->variables[YoyoCompiler::yoprefix('resolver')]
                            ?? self::request()->get(YoyoCompiler::yoprefix('resolver'));

        $componentSource = $this->variables[YoyoCompiler::yoprefix('source')]
                            ?? self::request()->get(YoyoCompiler::yoprefix('source'));

        if ($componentSource) {
            $this->variables[YoyoCompiler::yoprefix('source')] = $componentSource;
        }

        if ($resolverName && isset(self::$componentResolver[$resolverName])) {
            return new self::$componentResolver[$resolverName]($this->id, $this->name, $this->variables, self::$viewProviders);
        }

        return new ComponentResolver($this->id, $this->name, $this->variables, self::$viewProviders);
    }

    public function registerViewProvider(...$params)
    {
        $viewProvider = array_pop($params);

        if (! empty($params)) {
            self::$viewProviders[$params] = $viewProvider;
        }

        self::$viewProviders['default'] = $viewProvider;
    }

    public function registerViewProviders($providers)
    {
        foreach ($providers as $key => $provider) {
            $this->registerViewProvider($key, $provider);
        }
    }

    public static function getViewProvider($name = 'default')
    {
        return self::$viewProviders[$name]();
    }

    public function registerComponentResolver($name, $resolverClass)
    {
        if (! ClassHelpers::classImplementsInterface($resolverClass, ComponentResolverInterface::class)) {
            throw new \Exception("Component resolver [$resolverClass] does not implement [ComponentResolverInterface] interface");
        }

        self::$componentResolver[$name] = $resolverClass;
    }

    public function registerComponents($components): void
    {
        ComponentManager::registerComponents($components);
    }

    public function registerComponent($name, $class): void
    {
        ComponentManager::registerComponent($name, $class);
    }

    public function mount($name, $variables = [], $attributes = [], $action = 'render'): self
    {
        $this->action($action);

        $this->id = $this->getComponentId($attributes);

        unset($attributes['id']);

        $this->name = $name;

        $this->variables = $variables;

        $this->attributes = $attributes;

        return $this;
    }

    public function action($action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Renders the component on initial page load.
     */
    public function render(): string
    {
        return $this->output($spinning = false);
    }

    /**
     * Renders the component on dynamic updates (ajax) to send back to the browser.
     */
    public function refresh(): string
    {
        return $this->output($spinning = true);
    }

    public function update(): string
    {
        [$name, $action] = $this->parseUpdateRequest();

        return $this->mount($name, $variables = [], $attributes = [], $action)->refresh();
    }

    protected function parseUpdateRequest()
    {
        $component = self::request()->get('component');

        $parts = array_filter(explode('/', $component));

        if (empty($parts)) {
            throw new IncompleteComponentParamInRequest();
        }

        $name = $parts[0];

        $action = $parts[1] ?? 'render';

        return [$name, $action];
    }

    public function output($spinning = false)
    {
        $variables = [];

        $componentManager = new ComponentManager(self::request(), $spinning);

        $componentManager->addComponentResolver($this->getComponentResolver());

        $html = $componentManager->process($this->id, $this->name, $this->action ?? YoyoCompiler::COMPONENT_DEFAULT_ACTION, $this->variables, $this->attributes);

        $defaultValues = $componentManager->getDefaultPublicVars();

        $newValues = $componentManager->getPublicVars();

        // Automatically include in request public properties, or request variables in the case of anonymous components

        $variables = array_merge($defaultValues, $newValues);

        $variables = YoyoHelpers::removeEmptyValues($variables);

        $listeners = $componentManager->getListeners();

        $compiledHtml = $this->compile($html, $spinning, $variables, $listeners);

        if ($spinning) {
            $queryStringKeys = $componentManager->getQueryString();

            $queryString = new QueryString($defaultValues, $newValues, $queryStringKeys);

            // Browser URL State

            $urlStateManager = new UrlStateManagerService();

            if ($componentManager->isDynamicComponent()) {
                $urlStateManager->pushState($queryString->getPageQueryParams());
            }

            // Browser Events

            $eventsService = BrowserEventsService::getInstance();

            $eventsService->dispatch();

            // Browser Redirect

            (PageRedirectService::getInstance())->redirect($componentManager->getComponentInstance()->redirectTo);
        }

        return (Response::getInstance())->send($compiledHtml);
    }

    public function compile($html, $spinning = null, $variables = [], $listeners = []): string
    {
        $spinning = $spinning ?? $this->is_spinning();

        $variables = array_merge($this->variables, $variables);

        $output = (new YoyoCompiler($this->id, $this->name, $variables, $this->attributes, $spinning))
                    ->addComponentListeners($listeners)
                    ->compile($html);

        return $output;
    }

    /**
     * Is this a request to update the component?
     */
    private function is_spinning(): bool
    {
        $spinning = self::request()->isYoyoRequest();

        // Stop spinning of child components when parent is refreshed

        self::request()->windUp();

        return $spinning;
    }
}
