<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class CounterDynamicProperties extends Component
{
    public function getQueryString()
    {
        return $this->addDynamicProperties();
    }

    public function addDynamicProperties()
    {
        return ['count'];
    }

    public function increment()
    {
        $this->count++;
    }

    public function getCurrentCountProperty()
    {
        return 'The count is now '.$this->count;
    }

    public function render()
    {
        return $this->view('counter');
    }
}
