<?php

namespace Clickfwd\Yoyo\Exceptions;

class ComponentNotFound extends \Exception
{
    public function __construct($alias)
    {
        parent::__construct("Component [$alias] not found.");
    }
}
