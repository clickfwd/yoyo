<?php

namespace Clickfwd\Yoyo\Exceptions;

class NonPublicComponentMethodCall extends \Exception
{
    public function __construct($componentName, $method)
    {
        parent::__construct("Unable to call non-public method [$method] in Yoyo component [$componentName].");
    }
}
