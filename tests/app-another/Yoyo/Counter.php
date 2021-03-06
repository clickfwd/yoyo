<?php

namespace Tests\AppAnother\Yoyo;

use Clickfwd\Yoyo\Component;

class Counter extends Component
{
    public $count = 0;

    protected $queryString = ['count'];

    protected $props = ['count'];

    public function increment()
    {
        $this->count++;

        $this->emit('counter:updated', ['count' => $this->count]);
    }

    public function getCurrentCountProperty()
    {
        return 'The count is now '.$this->count;
    }
}
