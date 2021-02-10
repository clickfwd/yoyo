<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class EmptyResponse extends Component
{
    public function mount()
    {
        return $this->skipRender();
    }
}
