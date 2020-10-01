<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ComputedProperty extends Component
{
    public $foo = 'bar';

    public function getFooBarProperty()
    {
        return $this->foo;
    }
}
