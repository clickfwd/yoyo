<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;
use Clickfwd\Yoyo\Yoyo;

class BrowserEventsService
{
    use Singleton;

    private $eventQueue = [];

    private $browserEventQueue = [];

    public function __construct()
    {
        $this->request = Yoyo::request();

        $this->response = Response::getInstance();
    }

    public function emit($event, ...$params)
    {
        $this->queue($event, $params);
    }

    public function emitTo($target, $event, ...$params)
    {
        $this->queue($event, $params, null, $target);
    }

    public function emitToWithSelector($target, $event, ...$params)
    {
        $this->queue($event, $params, $target);
    }

    public function emitSelf($event, ...$params)
    {
        if ($component = $this->getComponentNameFromRequest()) {
            $this->queue($event, $params, $selector = null, $component, 'self');
        }
    }

    public function emitUp($event, ...$params)
    {
        $targetId = $this->request->triggerId();
        if ($component = $this->getComponentNameFromRequest()) {
            $this->queue($event, $params, "#{$targetId}", $component, 'ancestorsOnly');
        }
    }

    public function queue($event, $params, $selector = null, $component = null, $propagation = null)
    {
        $params = is_array($params[0]) ? array_filter($params[0]) : $params[0];

        $payload = array_filter(compact('event', 'params', 'selector', 'component', 'propagation'));

        $this->eventQueue[] = $payload;
    }

    public function dispatchBrowserEvent($event, $params = [])
    {
        $params = is_array($params) ? array_filter($params) : $params;

        $this->browserEventQueue[] = compact('event', 'params');
    }

    public function dispatch()
    {
        $this->response->header('Yoyo-Emit', json_encode($this->eventQueue));

        $this->response->header('Yoyo-Browser-Event', json_encode($this->browserEventQueue));
    }

    protected function getComponentNameFromRequest()
    {
        if ($name = $this->request->get('component')) {
            return explode('/', $name)[0];
        }

        return false;
    }
}
