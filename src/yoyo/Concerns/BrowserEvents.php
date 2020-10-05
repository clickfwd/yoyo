<?php

namespace Clickfwd\Yoyo\Concerns;

use Clickfwd\Yoyo\Services\BrowserEventsService;

trait BrowserEvents
{
    public function emit($event, ...$params)
    {
        (BrowserEventsService::getInstance())->emit($event, $params);
    }

    public function emitTo($target, $event, ...$params)
    {
        (BrowserEventsService::getInstance())->emitTo($target, $event, $params);
    }

    public function emitSelf($event, ...$params)
    {
        (BrowserEventsService::getInstance())->emitSelf($event, $params);
    }

    public function emitUp($event, ...$params)
    {
        (BrowserEventsService::getInstance())->emitUp($event, $params);
    }

    public function dispatchBrowserEvent($event, $params = [])
    {
        (BrowserEventsService::getInstance())->dispatchBrowserEvent($event, $params);
    }
}
