<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;
use Tests\App\Concerns\WithFramework;

class ComponentWithTrait extends Component
{
    use WithFramework;

    public $output;

    public function mount()
    {
        $this->output = 'Component saw that ';
    }
}
