<?php

namespace Clickfwd\Yoyo\Concerns;

trait ResponseHeaders
{
    public function location($path)
    {
        $this->header('HX-Location', $path);

        return $this;
    }

    public function pushUrl($url)
    {
        $this->header('HX-Push-Url', $url);

        return $this;
    }

    public function redirect($url)
    {
        $this->header('HX-Redirect', $url);

        return $this;
    }

    public function refresh()
    {
        $this->header('HX-Refresh');

        return $this;
    }

    public function replaceUrl($url)
    {
        $this->header('HX-Replace-Url', $url);

        return $this;
    }

    public function reswap($swap)
    {
        $this->header('HX-Reswap', $swap);

        return $this;
    }

    public function retarget($selector)
    {
        $this->header('HX-Retarget', $selector);

        return $this;
    }

    public function trigger($event)
    {
        $this->header('HX-Trigger', $event);

        return $this;
    }

    public function triggerAfterSwap($event)
    {
        $this->header('HX-Trigger-After-Swap', $event);

        return $this;
    }

    public function triggerAfterSettle($event)
    {
        $this->header('HX-Trigger-After-Settle', $event);

        return $this;
    }
}
