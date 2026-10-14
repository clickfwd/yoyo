<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class History extends Component
{
    public $page = 1;

    public function goToPage($page)
    {
        $this->page = $page;
        $this->response->pushUrl('/history?page='.$page);
    }
}
