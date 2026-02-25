<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ComponentWithComputedArgs extends Component
{
    public $prefix = 'Hello';

    protected function getGreetingProperty($name = 'World')
    {
        return "{$this->prefix}, {$name}!";
    }

    protected function getExpensiveProperty()
    {
        static $callCount = 0;
        $callCount++;

        return "called:{$callCount}";
    }
}
