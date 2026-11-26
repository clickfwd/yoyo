<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class InlineTemplate extends Component
{
    public $message = 'Hello inline';

    public function render()
    {
        return $this->inline('<div>{{ $message }}</div>');
    }
}
