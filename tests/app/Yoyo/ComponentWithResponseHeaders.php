<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ComponentWithResponseHeaders extends Component
{
    public $message = 'initial';

    public function doRetarget()
    {
        $this->response->retarget('#other-target');
        $this->message = 'retargeted';
    }

    public function doReswap()
    {
        $this->response->reswap('innerHTML');
        $this->message = 'reswapped';
    }

    public function doReselect()
    {
        $this->response->reselect('#selected-part');
        $this->message = 'reselected';
    }

    public function doLocation()
    {
        $this->response->location('/new-location');
    }

    public function doPushUrl()
    {
        $this->response->pushUrl('/pushed-url');
        $this->message = 'url-pushed';
    }

    public function doReplaceUrl()
    {
        $this->response->replaceUrl('/replaced-url');
        $this->message = 'url-replaced';
    }

    public function doRedirect()
    {
        $this->response->redirect('/redirected');
    }

    public function doRefresh()
    {
        $this->response->refresh();
    }

    public function doTrigger()
    {
        $this->response->trigger('custom-event');
        $this->message = 'triggered';
    }

    public function doTriggerAfterSwap()
    {
        $this->response->triggerAfterSwap('swap-event');
        $this->message = 'trigger-after-swap';
    }

    public function doTriggerAfterSettle()
    {
        $this->response->triggerAfterSettle('settle-event');
        $this->message = 'trigger-after-settle';
    }
}
