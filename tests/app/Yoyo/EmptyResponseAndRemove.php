<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class EmptyResponseAndRemove extends Component
{
    public function mount()
    {
        return $this->skipRenderAndRemove();
    }
}
