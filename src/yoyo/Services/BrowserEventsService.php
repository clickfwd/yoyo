<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class BrowserEventsService
{
    use Singleton;

    private $eventQueue = [];

    private $browserEventQueue = [];

    public function __construct()
    {
        $this->request = Request::getInstance();

        $this->response = Response::getInstance();
    }

    public function emit($event, ...$params)
    {
        $this->queue($event, $params);
    }

    public function emitTo($target, $event, ...$params)
    {
        $selector = null;
        $component = null;

        if (in_array($target[0], ['.', '#'])) {
            $selector = $target;
        } else {
            $component = $target;
        }

        $this->queue($event, $params, $selector, $component);
    }

    public function emitSelf($event, ...$params)
    {
        if ($targetId = $this->request->target()) {
            $this->emitTo("#{$targetId}", $event, $params);
        }
    }

    public function emitUp($event, ...$params)
    {
        if ($targetId = $this->request->target()) {
            $this->queue($event, $params, "#{$targetId}", $component = null, $ancestorsOnly = true);
        }
    }

    public function queue($event, $params, $selector = null, $component = null, $ancestorsOnly = null)
    {
        $params = $params[0];

        $payload = array_filter(compact('event', 'params', 'selector', 'component', 'ancestorsOnly'));

        $this->eventQueue[] = $payload;
    }

    public function dispatchBrowserEvent($event, $params = [])
    {
        $this->browserEventQueue[] = compact('event', 'params');
    }

    public function dispatch()
    {
        $this->response->header('Yoyo-Emit', json_encode($this->eventQueue));

        $this->response->header('Yoyo-Browser-Event', json_encode($this->browserEventQueue));
    }
}
