<?php

namespace Clickfwd\Yoyo\Exceptions;

class ComponentNotFound extends \Exception
{
    public function __construct($alias)
    {
        parent::__construct("Yoyo component with alias [$alias] not found.");
    }
}
