<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ComponentWithSwapModifiers extends Component
{
    public function doSwap()
    {
        $this->addSwapModifiers('transition:true swap:500ms');
    }
}
