<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class FavoriteButton extends Component
{
    public $itemId;

    public $isFavorited = 0;

    protected $props = ['itemId', 'isFavorited'];

    public function toggle()
    {
        $this->isFavorited = $this->isFavorited ? 0 : 1;
    }
}
