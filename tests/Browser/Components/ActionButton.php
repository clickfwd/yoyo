<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class ActionButton extends Component
{
    public $label;

    protected $props = ['label'];

    public function fire()
    {
        $this->emit('notification');
    }
}
