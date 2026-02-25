<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class StatusDropdown extends Component
{
    public $itemId;

    public $status = 'draft';

    public $isOpen = false;

    public $newStatus;

    protected $props = ['itemId', 'status'];

    public function toggleMenu()
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function setStatus()
    {
        $this->status = $this->newStatus;
        $this->isOpen = false;
    }
}
