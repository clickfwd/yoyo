<?php

namespace Clickfwd\Yoyo\Exceptions;

class ComponentMethodNotFound extends \Exception
{
    public function __construct($component, $method)
    {
        parent::__construct(
            "Public method [{$method}] not found on Yoyo component [{$component}]"
        );
    }
}
