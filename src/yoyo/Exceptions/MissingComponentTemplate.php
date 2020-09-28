<?php

namespace Clickfwd\Yoyo\Exceptions;

class MissingComponentTemplate extends \Exception
{
    public function __construct($template, $componentName)
    {
        parent::__construct("Unable to find template [$template] for [$componentName] component.");
    }
}
