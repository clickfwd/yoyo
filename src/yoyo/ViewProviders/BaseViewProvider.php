<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;

abstract class BaseViewProvider
{
    public function getProviderInstance()
    {
        return $this->view;
    }
}
