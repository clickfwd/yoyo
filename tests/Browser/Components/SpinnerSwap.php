<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class SpinnerSwap extends Component
{
    public $saved = false;

    protected $props = ['saved'];

    public function save()
    {
        $this->saved = true;
    }
}
