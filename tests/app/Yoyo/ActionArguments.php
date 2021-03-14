<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ActionArguments extends Component
{
    public function someAction($a, $b)
    {
        $this->a = $a;

        $this->b = $b;
    }

    public function render()
    {
        return $this->view('action-arguments', ['a' => $this->a, 'b' => $this->b]);
    }
}
