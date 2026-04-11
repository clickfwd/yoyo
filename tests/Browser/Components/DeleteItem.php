<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class DeleteItem extends Component
{
    public $itemId;

    public $title;

    protected $props = ['itemId', 'title'];

    public function delete()
    {
        $this->skipRender();
    }
}
