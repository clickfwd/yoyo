<?php

namespace Clickfwd\Yoyo\Blade;

use Clickfwd\Yoyo\Yoyo;
use Illuminate\View\Component;

class CreateBladeViewFromString extends Component
{
    public function __invoke($contents)
    {
        $view = Yoyo::getViewProvider()->getProviderInstance();

        return $this->createBladeViewFromString($view, $contents);
    }

    public function render()
    {
        //
    }
}
