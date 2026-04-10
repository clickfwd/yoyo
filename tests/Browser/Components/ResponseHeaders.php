<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class ResponseHeaders extends Component
{
    public $message = 'initial';

    public function doRetarget()
    {
        $this->response->retarget('#retarget-receiver');
        $this->message = 'retargeted content';
    }

    public function doReswap()
    {
        $this->response->reswap('innerHTML');
        $this->message = 'inner-swapped';
    }

    public function doTrigger()
    {
        $this->response->trigger('custom-event');
        $this->message = 'triggered';
    }

    public function doTriggerAfterSettle()
    {
        $this->response->triggerAfterSettle('settle-event');
        $this->message = 'settled';
    }

    public function doPushUrl()
    {
        $this->response->pushUrl('/pushed-path');
        $this->message = 'url-pushed';
    }

    public function doReplaceUrl()
    {
        $this->response->replaceUrl('/replaced-path');
        $this->message = 'url-replaced';
    }
}
