<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\Exceptions\BypassRenderMethod;
use Clickfwd\Yoyo\Exceptions\HttpException;
use Clickfwd\Yoyo\Exceptions\NotFoundHttpException;
use Clickfwd\Yoyo\Interfaces\RequestInterface;
use Clickfwd\Yoyo\Services\BrowserEventsService;
use Clickfwd\Yoyo\Services\Configuration;
use Clickfwd\Yoyo\Services\PageRedirectService;
use Clickfwd\Yoyo\Services\Response;
use Clickfwd\Yoyo\Services\UrlStateManagerService;
use Illuminate\Container\Container;
use Psr\Container\ContainerInterface;

class Yoyo
{
    private $action;

    private $attributes = [];

    private $id;

    private $name;
    
    private $variables = [];

    private static $container;

    private static $request;

    private static $registeredComponents = [];

    private static $componentNamespaces = [];

    private static $resolverInstances = [];

    public function __construct(ContainerInterface $container = null)
    {
        static::$container = $container ?? Container::getInstance();
    }

    /**
     * Not really an instance, but we avoid having to call `new` with an empty constructor
     * Nested components don't work when re-using an instance
     */
    public static function getInstance()
    {
        return new Self(static::$container);
    }

    public function bindRequest(RequestInterface $request)
    {
        static::$request = $request;
    }

    public static function request()
    {
        if (! static::$request) {
            static::$request = new Request();
        }

        return static::$request;
    }

    public function configure($options): void
    {
        Configuration::getInstance($options);
    }

    public static function container()
    {
        return static::$container;
    }

    public function getComponentId($attributes): string
    {
        if (isset($attributes['id'])) {
            $id = $attributes['id'];
        } else {
            $id = static::request()->get(YoyoCompiler::yoprefix_value('id'), YoyoCompiler::yoprefix_value(YoyoHelpers::randString()));
        }

        // Remove the component ID from the request so it's not passed to child components
        static::request()->drop(YoyoCompiler::yoprefix_value('id'));

        return $id;
    }

    private function getComponentResolver()
    {
        $name = $this->variables[YoyoCompiler::yoprefix('resolver')]
                            ?? static::request()->get(YoyoCompiler::yoprefix('resolver'));

        if ($name && static::$container->bound("yoyo.resolver.{$name}")) {
            return static::$container->get("yoyo.resolver.{$name}")(static::$container, static::$registeredComponents, static::$componentNamespaces);
        }
        
        $resolver = ! $name ? new ComponentResolver() : static::$resolverInstances[$name];

        $name = $name ?? 'default';
        
        static::$container->instance("yoyo.resolver.{$name}", $resolver);
              
        return $resolver(static::$container, static::$registeredComponents, static::$componentNamespaces);
    }

    public function registerViewProvider($name, $provider = null)
    {
        if (is_null($provider)) {
            $provider = $name;
            $name = 'default';
        }

        static::$container->singleton("yoyo.view.{$name}", $provider);
    }

    public function registerViewProviders($providers)
    {
        foreach ($providers as $name => $provider) {
            $this->registerViewProvider($name, $provider);
        }
    }

    public static function getViewProvider($name = 'default')
    {
        return static::$container->get("yoyo.view.{$name}");
    }

    public function registerComponentResolver($resolver)
    {
        if (is_string($resolver)) {
            $resolver = self::$container->make($resolver);
        }

        $this->registerViewProvider($name = $resolver->getName(), function () use ($resolver) {
            return $resolver->getViewProvider();
        });

        static::$resolverInstances[$name] = $resolver;
    }

    public static function componentNamespace($namespace, $class): void
    {
        static::$componentNamespaces[$namespace] = $class;
    }
        
    public static function registerComponent($name, $class = null): void
    {
        static::$registeredComponents[$name] = $class;
    }

    public static function registerComponents($components): void
    {
        foreach ($components as $name => $class) {
            if (is_numeric($name)) {
                $name = $class;
                $class = null;
            }
            static::registerComponent($name, $class);
        }
    }

    public static function abort($code, $message = '', array $headers = [])
    {
        if ($code == 404) {
            throw new NotFoundHttpException($message, $headers);
        }

        throw new HttpException($code, $message, $headers);
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
        $this->action = $action == 'refresh' ? 'render' : $action;

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
        $output = $this->output($spinning = true);

        return $output;
    }

    public function update(): string
    {
        [$name, $action] = $this->parseUpdateRequest();

        return $this->mount($name, $variables = [], $attributes = [], $action)->refresh();
    }

    protected function parseUpdateRequest()
    {
        $component = static::request()->get('component');

        $parts = array_filter(explode('/', $component));

        if (empty($parts)) {
            throw new Exceptions\IncompleteComponentParamInRequest();
        }

        $name = $parts[0];

        $action = $parts[1] ?? 'render';

        return [$name, $action];
    }

    public function output($spinning = false)
    {
        $variables = [];

        $componentManager = new ComponentManager($this->getComponentResolver(), static::request(), $spinning);

        try {
            try {
                $html = $componentManager->process($this->id, $this->name, $this->action ?? YoyoCompiler::COMPONENT_DEFAULT_ACTION, $this->variables, $this->attributes);
            } finally {
                if ($componentManager->getComponentInstance()) {
                    // Get all data needed to pass the rendered HTML through the Yoyo compiler to make it reactive
                        
                    $defaultValues = $componentManager->getDefaultPublicVars();

                    $newValues = $componentManager->getPublicVars();

                    // Get dynamic component public properties anonymous components vars to pass them to the compiler
                    // Any matching parameter names in yoyo:props will be automatically added to yoyo:vals

                    $variables = array_merge($defaultValues, $newValues);

                    $variables = YoyoHelpers::removeEmptyValues($variables);

                    $listeners = $componentManager->getListeners();

                    $componentType = $componentManager->isDynamicComponent() ? 'dynamic' : 'anonymous';

                    // For dynamic components, filter variables based on component props
                        
                    $props = $componentManager->getProps();

                    $postComponentProcessingActions = function () use ($componentManager, $defaultValues, $newValues) {
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
                    };
                }
            }
        } catch (BypassRenderMethod $e) {
            if (isset($postComponentProcessingActions)) {
                $postComponentProcessingActions();
            }
            if ($e->getCode() == 204) {
                Response::getInstance()->status(204)->send();
                // Need to throw exception to stop execution and send 204 status code
                throw $e;
            } else {
                return Response::getInstance()->status(200)->send();
            }
        } catch (HttpException $e) {
            if (isset($postComponentProcessingActions)) {
                $postComponentProcessingActions();
            }
            Response::getInstance()->status($e->getStatusCode())->setHeaders($e->getHeaders())->send();

            throw $e;
        } catch (\Exception $e) {
            if (isset($postComponentProcessingActions)) {
                $postComponentProcessingActions();
            }
            Response::getInstance()->send();

            throw $e;
        }

        $cacheHistory = ! empty(array_filter($componentManager->getQueryString(),
            function($key) {
                return strpos($key, 'yoyo-id') !== 0;
            })
        );
        
        $compiledHtml = $this->compile($componentType, $html, $spinning, $variables, $listeners, $props, $cacheHistory);

        if ($spinning) {
            $postComponentProcessingActions();
        }

        return (Response::getInstance())->send($compiledHtml);
    }

    public function compile($componentType, $html, $spinning = null, $variables = [], $listeners = [], $props = [], $cacheHistory = false): string
    {
        $spinning = $spinning ?? $this->is_spinning();

        $variables = array_merge($this->variables, $variables);

        $output = (new YoyoCompiler($componentType, $this->id, $this->name, $variables, $this->attributes, $spinning))
                    ->withHistory($cacheHistory)
                    ->addComponentListeners($listeners)
                    ->addComponentProps($props)
                    ->compile($html);

        return $output;
    }

    /**
     * Is this a request to update the component?
     */
    private function is_spinning(): bool
    {
        $spinning = static::request()->isYoyoRequest();

        // Stop spinning of child components when parent is refreshed

        static::request()->windUp();

        return $spinning;
    }
}
