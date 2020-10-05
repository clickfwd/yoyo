<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Concerns\Singleton;
use Clickfwd\Yoyo\Exceptions\IncompleteComponentParamInRequest;
use Clickfwd\Yoyo\Interfaces\View as ViewInterface;
use Clickfwd\Yoyo\Services\BrowserEventsService;
use Clickfwd\Yoyo\Services\Configuration;
use Clickfwd\Yoyo\Services\Request;
use Clickfwd\Yoyo\Services\Response;
use Clickfwd\Yoyo\Services\UrlStateManagerService;

class Yoyo
{
    use Singleton;

    private $request;

    private static $view;

    private $id;

    private $name;

    private $action;

    private $variables = [];

    private $attributes = [];

    public function __construct()
    {
        // Need the same instance for all components to prevent nested components
        // from inheriting the parent component ID
        if (! $this->request) {
            $this->request = Request::getInstance();
        }
    }

    public function configure($options): void
    {
        Configuration::getInstance($options);
    }

    public function setViewProvider(ViewInterface $view): void
    {
        self::$view = $view;
    }

    public static function getViewProvider()
    {
        return self::$view;
    }

    public function registerComponents($components): void
    {
        ComponentManager::registerComponents($components);
    }

    public function registerComponent($name, $class): void
    {
        ComponentManager::registerComponent($name, $class);
    }

    public function getComponentId($attributes): string
    {
        if (isset($attributes['id'])) {
            $id = $attributes['id'];
        } else {
            $id = $this->request->input(YoyoCompiler::yoprefix_value('id'), YoyoCompiler::yoprefix_value(YoyoHelpers::randString()));
        }

        return $id;
    }

    public function mount($name, $variables = [], $attributes = [], $action = 'render'): self
    {
        $this->action($action);

        $this->id = $this->getComponentId($attributes);

        unset($attributes['id']);

        // Revove the component ID from the request so it's not passed to child components
        $this->request->drop(YoyoCompiler::yoprefix_value('id'));

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
        $component = $this->request->input('component');

        $parts = array_filter(explode('/', $component));

        if (empty($parts)) {
            throw new IncompleteComponentParamInRequest();
        }

        $name = $parts[0];

        $action = $parts[1] ?? 'render';

        return [$name, $action];
    }

    public function output($spinning = false): string
    {
        $componentManager = new ComponentManager($this->request, $this->id, $this->name, $spinning);

        $html = $componentManager->process($this->action ?? YoyoCompiler::COMPONENT_DEFAULT_ACTION, $this->variables, $this->attributes);

        $defaultValues = $componentManager->getDefaultPropertyValues();

        $newValues = $componentManager->getPublicPropertyValues();

        $queryStringKeys = $componentManager->getQueryString();

        $variables = [];

        // Automatically add GET request variables to the component for sub-sequent requests

        if ($spinning) {
            $queryString = new QueryString($defaultValues, $newValues, $queryStringKeys);

            $variables = $queryString->getQueryParams($defaultValues, $newValues, $queryStringKeys);
        }

        $listeners = $componentManager->getListeners();

        $compiledHtml = $this->compile($html, $spinning, $variables, $listeners);

        if ($spinning) {
            // Browser URL State

            $urlStateManager = new UrlStateManagerService();

            if ($componentManager->isDynamicComponent()) {
                $urlStateManager->pushState($queryString->getPageQueryParams());
            }

            // Browser Events

            $eventsService = BrowserEventsService::getInstance();

            $eventsService->dispatch();
        }

        return (Response::getInstance())->send($compiledHtml);
    }

    public function compile($html, $spinning = null, $variables = [], $listeners = []): string
    {
        $spinning = $spinning ?? self::is_spinning();

        $variables = array_merge($this->variables, $variables);

        $output = (new YoyoCompiler($this->id, $this->name, $variables, $this->attributes, $spinning))
                    ->addComponentListeners($listeners)
                    ->compile($html);

        return $output;
    }

    /**
     * Is this a request to update the component?
     */
    public static function is_spinning(): bool
    {
        $instance = self::getInstance();

        $spinning = $instance->request->isYoyoRequest();

        // Stop spinning of child components when parent is refreshed

        $instance->request->windUp();

        return $spinning;
    }
}
