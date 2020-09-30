<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class Counter extends Component
{
    public $count = 0;

    protected $queryString = ['count'];

    public function increment()
    {
        $this->count++;

        $this->emit('counter:updated', ['count' => $this->count]);
    }

    public function decrement()
    {
        $this->count--;

        $this->emit('counter:updated', ['count' => $this->count]);
    }

    public function _getCount()
    {
        return 'The count is now '.$this->count;
    }
}
