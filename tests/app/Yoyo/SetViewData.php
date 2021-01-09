<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class SetViewData extends Component
{
    public function mount()
    {
        $this->set('foo','bar');

        $this->set(['bar' => 'baz']);
    }
}
