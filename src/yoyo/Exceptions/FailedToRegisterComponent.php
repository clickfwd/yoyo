<?php

namespace Clickfwd\Yoyo\Exceptions;

class FailedToRegisterComponent extends \Exception
{
    public function __construct($alias, $componentClassName)
    {
        $message = 'Component registration failed.';

        if ($componentClassName == 'Anonymous') {
            $message = PHP_EOL."[$alias] template not found for [$componentClassName] component.";
        } else {
            $message = PHP_EOL."Component class [$componentClassName] provided for alias [$alias] not found.";
        }

        parent::__construct($message);
    }
}
