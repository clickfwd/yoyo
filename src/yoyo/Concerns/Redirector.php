<?php

namespace Clickfwd\Yoyo\Concerns;

trait Redirector
{
    public $redirectTo;

    public function redirect($url)
    {
        $this->redirectTo = $url;
    }
}
