<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class BrowserEventsService
{
    use Singleton;

    private $eventQueue = [];

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
            $this->queue($event, $params, "#{$targetId}", $component = null, $parentsOnly = true);
        }
    }

    public function queue($event, $params, $selector = null, $component = null, $parentsOnly = null)
    {
        $params = $params[0];

        $payload = array_filter(compact('event','params','selector','component','parentsOnly'));
        
        $this->eventQueue[] = $payload;
    }

    public function dispatch()
    {
        $this->response->header('Yoyo-Emit', json_encode($this->eventQueue));
    }
}
