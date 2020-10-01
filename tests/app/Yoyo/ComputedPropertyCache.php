<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ComputedPropertyCache extends Component
{
    protected $count = 1;

    public function getTestCountProperty()
    {
        return $this->count++;
    }
}