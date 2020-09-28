<?php

namespace Clickfwd\Yoyo\Exceptions;

class NonPublicComponentMethodCall extends \Exception
{
    public function __construct($componentName, $method)
    {
        parent::__construct("[$componentName] component method [$method] not found.");
    }
}
