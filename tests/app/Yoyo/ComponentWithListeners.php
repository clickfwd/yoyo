<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ComponentWithListeners extends Component
{
    public $message = '';

    protected $listeners = [
        'itemAdded' => 'onItemAdded',
        'refresh',
    ];

    public function onItemAdded()
    {
        $this->message = 'item was added';
    }
}
