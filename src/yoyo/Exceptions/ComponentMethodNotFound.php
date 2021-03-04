<?php

namespace Clickfwd\Yoyo\Exceptions;

class ComponentMethodNotFound extends \Exception
{
    public function __construct($component, $method)
    {
        parent::__construct(
            "Unable to call component method. Public method [{$method}] not found on component: [{$component}]"
        );
    }
}
