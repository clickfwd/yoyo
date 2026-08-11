<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class NullProp extends Component
{
    public $iconSlot = null;

    public $enabled = false;

    public $clicks = 0;

    protected $props = ['iconSlot', 'enabled'];

    public function increment()
    {
        $this->clicks++;
    }
}
