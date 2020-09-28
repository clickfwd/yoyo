<?php

namespace Clickfwd\Yoyo\Concerns;

use Clickfwd\Yoyo\Services\BrowserEventsService;

trait BrowserEvents
{
    public function emit($eventName, $payload = [])
    {
        (BrowserEventsService::getInstance())->emit($eventName, $payload);
    }

    public function emitTo($target, $eventName, $payload = [])
    {
        (BrowserEventsService::getInstance())->emitTo($target, $eventName, $payload);
    }

    public function emitSelf($eventName, $payload = [])
    {
        (BrowserEventsService::getInstance())->emitSelf($eventName, $payload);
    }

    public function emitUp($eventName, $payload = [])
    {
        (BrowserEventsService::getInstance())->emitUp($eventName, $payload);
    }
}
