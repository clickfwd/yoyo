<?php

namespace Tests\App\Yoyo;

use Clickfwd\Yoyo\Component;

class ChildRequestContext extends Component
{
    public $selection = 'default';

    public ?string $menuId = 'default';

    public function mount(?string $menuId = 'default')
    {
        $this->menuId = $menuId;
    }
}
