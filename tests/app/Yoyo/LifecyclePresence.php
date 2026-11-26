<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class LifecyclePresence extends Component
{
    public string $initialized;

    public string $mounted;

    public function initialize()
    {
        $this->initialized = 'from-initialize';
    }

    public function mount()
    {
        $this->mounted = 'from-mount';
    }

    public function render()
    {
        return $this->view('lifecycle-injection', [
            'result' => $this->initialized . ' ' . $this->mounted,
        ]);
    }
}
