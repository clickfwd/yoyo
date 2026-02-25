<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class NotificationBadge extends Component
{
    public $count = 0;

    protected $props = ['count'];

    protected $listeners = ['notification' => 'onNotification'];

    public function onNotification()
    {
        $this->count = $this->count + 1;
    }
}
