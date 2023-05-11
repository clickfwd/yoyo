<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

#[\AllowDynamicProperties]
class CounterDynamicProperties extends Component
{
    public function getQueryString()
    {
        return $this->getDynamicProperties();
    }

    /**
     * The 'count' property value is not known ahead of time and can be set programatically;
     *
     * @return void
     */
    public function getDynamicProperties()
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
