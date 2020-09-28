<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class BrowserEventsService
{
    use Singleton;

    private $browserEvents = [];

    public const BROWSER_EVENTS_NAMESPACE = 'yoyo';

    public function __construct()
    {
        $this->request = Request::getInstance();

        $this->response = Response::getInstance();
    }

    public function emit($eventName, $payload = [])
    {
        $this->queue($eventName, ['params' => $payload]);
    }

    public function emitTo($target, $eventName, $payload = [])
    {
        $detail = [
            'params' => $payload,
        ];

        if (in_array($target[0], ['.', '#'])) {
            $detail['selector'] = $target;
        } else {
            $detail['component'] = $target;
        }

        $this->queue($eventName, $detail);
    }

    public function emitSelf($eventName, $payload = [])
    {
        if ($targetId = $this->request->target()) {
            $this->emitTo("#{$targetId}", $eventName, $payload);
        }
    }

    public function emitUp($eventName, $payload = [])
    {
        if ($targetId = $this->request->target()) {
            $detail = [
                'params' => $payload,
                'selector' => "#{$targetId}",
                'parentsOnly' => true,
            ];

            $this->queue($eventName, $detail);
        }
    }

    public function queue($name, $detail)
    {
        $this->browserEvents[self::BROWSER_EVENTS_NAMESPACE.":$name"] = $detail;
    }

    public function dispatchBrowserEvents()
    {
        if (! empty($this->browserEvents)) {
            $events = json_encode($this->browserEvents);

            $this->response->header('HX-Trigger', $events);
        }
    }
}
