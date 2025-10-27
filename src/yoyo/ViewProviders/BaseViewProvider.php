<?php

namespace Clickfwd\Yoyo\ViewProviders;

abstract class BaseViewProvider
{
    protected $view;

    public function getProviderInstance()
    {
        return $this->view;
    }
}
