<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class ModalTrigger extends Component
{
    public $isOpen = 0;

    public $modalTitle = '';

    public function openModal()
    {
        $this->isOpen = 1;
    }

    public function closeModal()
    {
        $this->isOpen = 0;
    }
}
