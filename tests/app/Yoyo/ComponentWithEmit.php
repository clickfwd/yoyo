<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ComponentWithEmit extends Component
{
    public function doEmit()
    {
        $this->emit('testEvent', ['key' => 'value']);
    }

    public function doEmitTo()
    {
        $this->emitTo('other-component', 'targetEvent', ['id' => 1]);
    }

    public function doBrowserEvent()
    {
        $this->dispatchBrowserEvent('notification', ['message' => 'done']);
    }
}
