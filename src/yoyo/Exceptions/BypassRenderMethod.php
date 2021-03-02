<?php

namespace Clickfwd\Yoyo\Exceptions;

class BypassRenderMethod extends \Exception
{
    public function __construct($statusCode) {
        parent::__construct('', $statusCode);
    }
}
