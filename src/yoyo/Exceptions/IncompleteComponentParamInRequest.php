<?php

namespace Clickfwd\Yoyo\Exceptions;

class IncompleteComponentParamInRequest extends \Exception
{
    public function __construct()
    {
        parent::__construct('The component parameter is missing the component name or action.');
    }
}
