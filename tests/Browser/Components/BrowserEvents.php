<?php

namespace Tests\Browser\Components;

use Clickfwd\Yoyo\Component;

class BrowserEvents extends Component
{
    public $message = 'initial';

    public $page = 1;

    protected $queryString = ['page'];

    public function save()
    {
        $this->message = 'saved';
        $this->dispatchBrowserEvent('record-saved', ['message' => $this->message, 'id' => 42]);
    }

    public function saveWithoutSwap()
    {
        $this->save();
        $this->response->reswap('none');
    }

    public function saveWithoutRender()
    {
        $this->save();
        $this->skipRender();
    }

    public function goToPage($page)
    {
        $this->page = $page;
        $this->message = 'Page '.$page;
        $this->dispatchBrowserEvent('record-saved', ['page' => $page]);
    }
}
