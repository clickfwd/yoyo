<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class Registered extends Component
{
    public function render()
    {
        return $this->view('registered');
    }
}
