<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class Counter extends Component
{
    public $count = 0;

    protected $queryString = ['count'];

    protected $props = ['count'];

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }
}
