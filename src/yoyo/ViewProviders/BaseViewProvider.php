<?php

namespace Clickfwd\Yoyo\ViewProviders;

use Clickfwd\Yoyo\Interfaces\ViewProviderInterface;

abstract class BaseViewProvider
{
    protected $view;
    
    public function getProviderInstance()
    {
        return $this->view;
    }
}
