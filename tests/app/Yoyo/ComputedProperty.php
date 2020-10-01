<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ComputedProperty extends Component
{
    public $foo = 'bar';

    protected function getFooBarProperty()
    {
        return $this->foo;
    }
}
