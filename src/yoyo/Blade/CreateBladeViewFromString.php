<?php

namespace Clickfwd\Yoyo\Blade;

use Illuminate\View\Component;

class CreateBladeViewFromString extends Component
{
    public function __invoke($view, $contents)
    {
        return $this->createBladeViewFromString($view, $contents);
    }

    public function render()
    {
        //
    }
}
